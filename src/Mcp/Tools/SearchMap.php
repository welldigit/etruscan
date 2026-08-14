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
use WellDigit\Etruscan\Mcp\Services\MapSearch;
use WellDigit\Etruscan\Payloads\ParsedNote;
use WellDigit\Etruscan\Utilities\EtruscanConfig;

#[IsReadOnly]
#[EtruscanNode('search-map')]
#[EtruscanLayer('tool')]
#[EtruscanContext('mcp')]
final class SearchMap extends Tool
{
    protected string $description = 'Search the codebase map by name or concept — matches node aliases, class names, taxonomy values and descriptions. Returns ranked nodes; follow up with lookup-node.';

    public function __construct(
        private readonly MapReader $mapReader,
        private readonly MapSearch $mapSearch,
        private readonly ConsultationRecorder $consultationRecorder,
    ) {}

    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('A class name, alias fragment or concept, e.g. "booking guard".')->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        $query = trim((string) $request->string('query'));

        if ($query === '') {
            return Response::error('Provide a search query.');
        }

        $notesByAlias = ($this->mapReader)();

        if ($notesByAlias === []) {
            return Response::error(MapReader::EMPTY_MAP_MESSAGE);
        }

        $matches = ($this->mapSearch)($notesByAlias, $query);

        $text = $matches === []
            ? 'No nodes match ['.$query.']. The concept may be missing from the map — consider annotating the class that owns it.'
            : implode("\n", array_map($this->line(...), $matches));

        ($this->consultationRecorder)(
            vaultPath: EtruscanConfig::vaultPath(),
            type: UsageEventType::Search,
            outcome: $matches === [] ? UsageOutcome::Miss : UsageOutcome::Hit,
            subject: $query,
            results: count($matches),
            servedText: $text,
        );

        return Response::text($text);
    }

    private function line(ParsedNote $parsedNote): string
    {
        $description = (string) preg_replace('/\s+/', ' ', $parsedNote->description);

        if (strlen($description) > 140) {
            $description = substr($description, 0, 137).'...';
        }

        $source = $parsedNote->frontmatter[IdentityFrontmatterKey::Source->value] ?? null;

        return sprintf(
            '- %s%s%s',
            $parsedNote->alias,
            is_string($source) ? ' ('.$source.')' : '',
            $description === '' ? '' : ' — '.$description,
        );
    }
}
