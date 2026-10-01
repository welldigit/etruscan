<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use WellDigit\Etruscan\Enums\UsageEventType;
use WellDigit\Etruscan\Enums\UsageOutcome;
use WellDigit\Etruscan\Mcp\Services\ConsultationRecorder;
use WellDigit\Etruscan\Mcp\Services\MapOverviewBuilder;
use WellDigit\Etruscan\Mcp\Services\MapReader;
use WellDigit\Etruscan\Utilities\EtruscanConfig;

#[IsReadOnly]
#[\EtruscanNode('map-overview')]
#[\EtruscanLayer('tool')]
#[\EtruscanContext('mcp')]
final class MapOverview extends Tool
{
    /**
     * Above this many members a group prints its count and an expand hint
     * instead of its aliases.
     *
     * Group count grows far more slowly than node count — 69 nodes here make
     * 9 groups — so leading with counts keeps this roughly flat as the map
     * grows, where listing every alias is linear in nodes. Degrading per group
     * rather than on a whole-map threshold means the shape of the taxonomy
     * always survives, and you can see which group is the fat one.
     */
    private const int GROUP_MEMBER_LIMIT = 25;

    protected string $description = 'Returns the whole codebase map as an index: every node alias, grouped under the values of one taxonomy axis. Aliases only — no descriptions, source paths or dependency links; use search-map or lookup-node for those. Use it to orient in an unfamiliar codebase, or to size the map before deciding where to look. Skip it when you already have an alias or a search term. A group of more than '.self::GROUP_MEMBER_LIMIT.' nodes prints its count instead of its members, and the group parameter then lists that one group in full. A node carrying several values of the axis is listed under each of them, so group counts can exceed the node count. Errors when no map has been generated yet.';

    public function __construct(
        private readonly MapReader $mapReader,
        private readonly MapOverviewBuilder $mapOverviewBuilder,
        private readonly ConsultationRecorder $consultationRecorder,
    ) {}

    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function schema(JsonSchema $schema): array
    {
        return [
            'axis' => $schema->string()->description('Frontmatter axis key to group by. The bundled keys are context, layer, domain and slice, but a project defines its own axes, so this map may carry others. Defaults to context. If the key is on no node, the tool falls back to the first axis it finds and says so in the reply. Nodes without the axis are grouped under (none).')->default('context'),
            'group' => $schema->string()->description('List one group in full, naming a value of the chosen axis — use it to expand a group that came back as a count. Omit it for the whole map.'),
        ];
    }

    public function handle(Request $request): Response
    {
        $requestedAxis = trim((string) $request->string('axis', 'context'));
        $group = trim((string) $request->string('group'));

        $markerKey = EtruscanConfig::markerKey();
        $notesByAlias = ($this->mapReader)();

        if ($notesByAlias === []) {
            return Response::error(MapReader::EMPTY_MAP_MESSAGE);
        }

        $overview = ($this->mapOverviewBuilder)($notesByAlias, $requestedAxis, $markerKey);

        if ($group !== '' && ! array_key_exists($group, $overview['groups'])) {
            return Response::error(sprintf(
                'No group [%s] on the [%s] axis. Groups: %s.',
                $group,
                $overview['axis'],
                implode(', ', array_keys($overview['groups'])),
            ));
        }

        $text = $group === ''
            ? $this->renderMap(
                overview: $overview,
                requestedAxis: $requestedAxis,
                nodeCount: count($notesByAlias),
            )
            : $this->renderGroup(
                aliases: $overview['groups'][$group],
                group: $group,
                axis: $overview['axis'],
                nodeCount: count($notesByAlias),
            );

        $text .= "\n\n".$this->mapReader->freshnessNotice();

        ($this->consultationRecorder)(
            vaultPath: EtruscanConfig::vaultPath(),
            type: UsageEventType::Overview,
            outcome: UsageOutcome::Hit,
            subject: $group === '' ? $overview['axis'] : $overview['axis'].':'.$group,
            results: $group === '' ? count($notesByAlias) : count($overview['groups'][$group]),
            servedText: $text,
        );

        return Response::text($text);
    }

    /**
     * @param  array{axis: string, available: list<string>, groups: array<string, list<string>>}  $overview
     */
    private function renderMap(array $overview, string $requestedAxis, int $nodeCount): string
    {
        $lines = [sprintf('%d nodes on the map, grouped by [%s]:', $nodeCount, $overview['axis'])];

        if ($requestedAxis !== $overview['axis']) {
            $lines[] = sprintf(
                'No node carries [%s], so the map is grouped by [%s] instead. Axes on this map: %s.',
                $requestedAxis,
                $overview['axis'],
                implode(', ', $overview['available']),
            );
        }

        $memberships = array_sum(array_map(count(...), $overview['groups']));

        if ($memberships !== $nodeCount) {
            $lines[] = sprintf(
                'Group counts total %d because a node carrying several values of [%s] is listed under each.',
                $memberships,
                $overview['axis'],
            );
        }

        foreach ($overview['groups'] as $value => $aliases) {
            $lines[] = '';
            $lines[] = sprintf('%s (%d):', $value, count($aliases));

            if (count($aliases) > self::GROUP_MEMBER_LIMIT) {
                $lines[] = TruncationNotice::line(
                    what: 'aliases',
                    shown: 0,
                    total: count($aliases),
                    retrieval: sprintf('call map-overview again with group="%s" to list them', $value),
                );

                continue;
            }

            foreach ($aliases as $alias) {
                $lines[] = '- '.$alias;
            }
        }

        return implode("\n", $lines);
    }

    /**
     * @param  list<string>  $aliases
     */
    private function renderGroup(array $aliases, string $group, string $axis, int $nodeCount): string
    {
        $lines = [sprintf(
            '%d nodes in [%s] on the [%s] axis, of %d on the map:',
            count($aliases),
            $group,
            $axis,
            $nodeCount,
        ), ''];

        foreach ($aliases as $alias) {
            $lines[] = '- '.$alias;
        }

        return implode("\n", $lines);
    }
}
