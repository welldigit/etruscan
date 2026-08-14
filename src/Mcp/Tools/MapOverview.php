<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use WellDigit\Etruscan\Attributes\EtruscanNode;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;
use WellDigit\Etruscan\Enums\UsageEventType;
use WellDigit\Etruscan\Enums\UsageOutcome;
use WellDigit\Etruscan\Mcp\Services\ConsultationRecorder;
use WellDigit\Etruscan\Mcp\Services\MapOverviewBuilder;
use WellDigit\Etruscan\Mcp\Services\MapReader;
use WellDigit\Etruscan\Utilities\EtruscanConfig;

#[IsReadOnly]
#[EtruscanNode('map-overview')]
#[EtruscanLayer('tool')]
#[EtruscanContext('mcp')]
final class MapOverview extends Tool
{
    protected string $description = 'Orient in the codebase in one call: every node on the map grouped by a taxonomy axis (context by default). Start here when the area is unfamiliar.';

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
            'axis' => $schema->string()->description('Taxonomy axis to group by, e.g. context, layer, domain. Default: context.'),
        ];
    }

    public function handle(Request $request): Response
    {
        $axis = trim((string) $request->string('axis', 'context'));

        $markerKey = EtruscanConfig::markerKey();
        $notesByAlias = ($this->mapReader)();

        if ($notesByAlias === []) {
            return Response::error(MapReader::EMPTY_MAP_MESSAGE);
        }

        $overview = ($this->mapOverviewBuilder)($notesByAlias, $axis, $markerKey);

        $lines = [sprintf('%d nodes on the map, grouped by [%s]:', count($notesByAlias), $overview['axis'])];

        foreach ($overview['groups'] as $value => $aliases) {
            $lines[] = '';
            $lines[] = sprintf('%s (%d):', $value, count($aliases));

            foreach ($aliases as $alias) {
                $lines[] = '- '.$alias;
            }
        }

        $text = implode("\n", $lines);

        ($this->consultationRecorder)(
            vaultPath: EtruscanConfig::vaultPath(),
            type: UsageEventType::Overview,
            outcome: UsageOutcome::Hit,
            subject: $overview['axis'],
            results: count($notesByAlias),
            servedText: $text,
        );

        return Response::text($text);
    }
}
