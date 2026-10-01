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
use WellDigit\Etruscan\Mcp\Services\MapReader;
use WellDigit\Etruscan\Mcp\Services\MapSearch;
use WellDigit\Etruscan\Utilities\EtruscanConfig;
use WellDigit\Etruscan\Utilities\NodeSummaryLine;

#[IsReadOnly]
#[\EtruscanNode('search-map')]
#[\EtruscanLayer('tool')]
#[\EtruscanContext('mcp')]
final class SearchMap extends Tool
{
    protected string $description = 'Ranked substring search over the codebase map. Scores every node on its alias (exact, then partial), class short name and FQCN, taxonomy values, description and human notes, then returns the '.MapSearch::RESULT_LIMIT.' highest-scoring nodes, one line each: alias, source path, and the description clipped to '.NodeSummaryLine::DESCRIPTION_LIMIT.' characters. Use it to turn a name or a concept into an alias you can pass to lookup-node or trace-node. It returns no taxonomy, no dependency links and no full description — lookup-node gives those, and map-overview gives the whole map rather than the top matches. Queries are recorded locally, misses included, so a search that finds nothing is useful signal rather than a wasted call.';

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
            'query' => $schema->string()->description('One search string, matched as a single case-insensitive substring — it is not tokenised, so a multi-word phrase matches only text containing that exact phrase. Prefer one distinctive term ("booking", "guard", "MonitorCreate") and search again with a different term rather than combining words.')->required(),
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
            ? 'No nodes match ['.$query.']. Try another term or source search; a miss can mean different terminology, stale data, or missing annotations.'
            : implode("\n", array_map(NodeSummaryLine::render(...), $matches));

        $text .= "\n\n".$this->mapReader->freshnessNotice();

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
}
