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
use WellDigit\Etruscan\Enums\IdentityFrontmatterKey;
use WellDigit\Etruscan\Enums\UsageEventType;
use WellDigit\Etruscan\Enums\UsageOutcome;
use WellDigit\Etruscan\Mcp\Services\ConsultationRecorder;
use WellDigit\Etruscan\Mcp\Services\MapReader;
use WellDigit\Etruscan\Mcp\Services\NodeLookup;
use WellDigit\Etruscan\Payloads\ParsedNote;
use WellDigit\Etruscan\Utilities\EtruscanConfig;

#[IsReadOnly]
#[EtruscanNode('lookup-node')]
#[EtruscanLayer('tool')]
#[EtruscanContext('mcp')]
final class LookupNode extends Tool
{
    protected string $description = 'Read one node of the codebase map in full: its human-written description, taxonomy, source file path, dependencies in both directions, and any further human notes. Use the source path to open the real file.';

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
            'alias' => $schema->string()->description('The node alias, e.g. "monitor-create".')->required(),
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

            $text = 'No node ['.$alias.'] on the map.'.$suggestions.' If this class should be on the map, annotate it with #[EtruscanNode] and regenerate.';

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
            $lines[] = '';
            $lines[] = 'Human notes (protected knowledge):';
            $lines[] = $parsedNote->manual;
        }

        $source = $parsedNote->frontmatter[IdentityFrontmatterKey::Source->value] ?? null;

        if (is_string($source)) {
            $lines[] = '';
            $lines[] = 'Open the implementation at: '.$source;
        }

        return implode("\n", $lines);
    }
}
