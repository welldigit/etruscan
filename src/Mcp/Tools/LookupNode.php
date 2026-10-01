<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use WellDigit\Etruscan\Enums\IdentityFrontmatterKey;
use WellDigit\Etruscan\Enums\UsageEventType;
use WellDigit\Etruscan\Enums\UsageOutcome;
use WellDigit\Etruscan\Mcp\Services\ConsultationRecorder;
use WellDigit\Etruscan\Mcp\Services\MapReader;
use WellDigit\Etruscan\Mcp\Services\NodeLookup;
use WellDigit\Etruscan\Payloads\ParsedNote;
use WellDigit\Etruscan\Utilities\EtruscanConfig;
use WellDigit\Etruscan\Utilities\TextClipper;

#[IsReadOnly]
#[\EtruscanNode('lookup-node')]
#[\EtruscanLayer('tool')]
#[\EtruscanContext('mcp')]
final class LookupNode extends Tool
{
    /**
     * The only thing on a note without a natural ceiling: arbitrary human
     * markdown. The description is a written-once spec and the dependency
     * lists are the blast radius, so both come back whole; this clip is
     * prophylactic, for the day someone pastes a design doc into a note.
     */
    private const int MANUAL_LIMIT = 1200;

    protected string $description = 'Returns one node of the codebase map in full: every identity and taxonomy field from its frontmatter, the human-written description, the outbound and inbound dependency aliases, any human notes kept below the generated blocks, and the source file path. Use it once you have an alias — from search-map, map-overview, trace-node, or a wikilink in another note — then open the file at the source path for implementation detail. The description and the dependency lists are never truncated; only a very long human-notes section is clipped, and the reply says so and names the file holding the rest. An unknown alias is not an error: the reply lists the closest aliases on the map, so a near miss is worth retrying before falling back to a file search.';

    public function __construct(
        private readonly MapReader $mapReader,
        private readonly NodeLookup $nodeLookup,
        private readonly ConsultationRecorder $consultationRecorder,
    ) {}

    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function schema(JsonSchema $schema): array
    {
        return [
            'alias' => $schema->string()->description('The node alias exactly as the map spells it — kebab-case, e.g. "monitor-create". Matching is exact; use search-map first if you are guessing.')->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        $alias = trim((string) $request->string('alias'));

        if ($alias === '') {
            return Response::error('Provide the node alias to look up.');
        }

        $notesByAlias = ($this->mapReader)();

        if ($notesByAlias === []) {
            return Response::error(MapReader::EMPTY_MAP_MESSAGE);
        }

        $lookup = ($this->nodeLookup)($notesByAlias, $alias);

        if ($lookup['note'] === null) {
            $suggestions = $lookup['suggestions'] === []
                ? ''
                : ' Closest aliases: '.implode(', ', $lookup['suggestions']).'.';

            $text = 'No node ['.$alias.'] on the map.'.$suggestions.' If this class should be on the map, annotate it with #[\\EtruscanNode(\'alias\')] — the leading backslash points at the global attribute — and regenerate.';

            $text .= "\n\n".$this->mapReader->freshnessNotice();

            ($this->consultationRecorder)(
                vaultPath: EtruscanConfig::vaultPath(),
                type: UsageEventType::Lookup,
                outcome: UsageOutcome::Miss,
                subject: $alias,
                results: 0,
                servedText: $text,
            );

            return Response::text($text);
        }

        $text = $this->render($lookup['note']);

        $text .= "\n\n".$this->mapReader->freshnessNotice();

        ($this->consultationRecorder)(
            vaultPath: EtruscanConfig::vaultPath(),
            type: UsageEventType::Lookup,
            outcome: UsageOutcome::Hit,
            subject: $alias,
            results: 1,
            servedText: $text,
        );

        return Response::text($text);
    }

    private function render(ParsedNote $parsedNote): string
    {
        $lines = ['# '.$parsedNote->alias];

        foreach ($parsedNote->frontmatter as $key => $value) {
            if ($key === EtruscanConfig::markerKey()) {
                continue;
            }

            $lines[] = $key.': '.(is_array($value) ? implode(', ', $value) : $value);
        }

        if ($parsedNote->description !== '') {
            $lines[] = '';
            $lines[] = $parsedNote->description;
        }

        if ($parsedNote->links !== []) {
            $lines[] = '';
            $lines[] = 'References: '.implode(', ', $parsedNote->links);
        }

        if ($parsedNote->referencedBy !== []) {
            $lines[] = 'Referenced by: '.implode(', ', $parsedNote->referencedBy);
        }

        if ($parsedNote->manual !== '') {
            $manual = TextClipper::clipAtLineBoundary($parsedNote->manual, self::MANUAL_LIMIT);

            $lines[] = '';
            $lines[] = 'Human notes (protected knowledge):';
            $lines[] = $manual;

            if ($manual !== $parsedNote->manual) {
                $lines[] = TruncationNotice::line(
                    what: 'characters of human notes',
                    shown: mb_strlen($manual),
                    total: mb_strlen($parsedNote->manual),
                    retrieval: $parsedNote->path !== null
                        ? 'read the whole section in '.$parsedNote->path
                        : 'read the whole section in this node\'s vault note',
                );
            }
        }

        $source = $parsedNote->frontmatter[IdentityFrontmatterKey::Source->value] ?? null;

        if (is_string($source)) {
            $lines[] = '';
            $lines[] = 'Open the implementation at: '.$source;
        }

        $lines[] = '';
        $lines[] = NoteTrustReminder::LINE;

        return implode("\n", $lines);
    }
}
