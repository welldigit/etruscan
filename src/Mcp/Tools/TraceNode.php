<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use WellDigit\Etruscan\Enums\TraceDirection;
use WellDigit\Etruscan\Enums\UsageEventType;
use WellDigit\Etruscan\Enums\UsageOutcome;
use WellDigit\Etruscan\Mcp\Services\ConsultationRecorder;
use WellDigit\Etruscan\Mcp\Services\MapReader;
use WellDigit\Etruscan\Mcp\Services\NodeTrace;
use WellDigit\Etruscan\Mcp\Services\SourceReferenceTrace;
use WellDigit\Etruscan\Utilities\EtruscanConfig;

#[IsReadOnly]
#[\EtruscanNode('trace-node')]
#[\EtruscanLayer('tool')]
#[\EtruscanContext('mcp')]
final class TraceNode extends Tool
{
    /** Number of annotated neighbours per direction whose descriptions are included. */
    private const int DEFAULT_DESCRIPTIONS = 8;

    private const int MAX_DESCRIPTIONS = 50;

    protected string $description = 'Trace one hop of references between annotated nodes, plus a paginated list of source evidence covering named classes in configured PHP roots, including unannotated callers. Evidence gives relationship kind and file:line. This is a static reference map, not a complete call graph or guaranteed change impact. Dynamic wiring and unscanned code can be absent. Every annotated neighbour alias is listed, with descriptions budgeted separately (default 8 per direction). Source evidence defaults to 20 occurrences; evidence_offset retrieves further pages and evidence_limit controls page size. Stale or missing local evidence is explicitly withheld; regenerate with etruscan:generate. Use direction in, out, or both.';

    public function __construct(
        private readonly MapReader $mapReader,
        private readonly NodeTrace $nodeTrace,
        private readonly SourceReferenceTrace $sourceReferenceTrace,
        private readonly ConsultationRecorder $consultationRecorder,
    ) {}

    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function schema(JsonSchema $schema): array
    {
        return [
            'alias' => $schema->string()->description('The node alias to trace from, exactly as the map spells it; use search-map first if you are guessing. An unknown alias returns a short message pointing at search-map, not an error.')->required(),
            'direction' => $schema->string()->description('out (what it uses), in (who uses it) or both. Default: both.')->enum(TraceDirection::class)->default(TraceDirection::Both->value),
            'evidence_offset' => $schema->integer()->description('Zero-based offset through source evidence, including unannotated callers. Default 0.')->min(0)->default(0),
            'evidence_limit' => $schema->integer()->description('Source occurrences per page, 0 to omit evidence. Default 20, maximum 50.')->min(0)->max(50)->default(20),
            'descriptions' => $schema->integer()->description('How many neighbour descriptions to inline per direction. Every alias is listed either way — this governs only how much prose comes with them. Lower it when tracing a hub; 0 returns aliases alone.')->min(0)->max(self::MAX_DESCRIPTIONS)->default(self::DEFAULT_DESCRIPTIONS),
        ];
    }

    public function handle(Request $request): Response
    {
        $alias = trim((string) $request->string('alias'));

        if ($alias === '') {
            return Response::error('Provide the node alias to trace.');
        }

        $traceDirection = TraceDirection::tryFrom((string) $request->string('direction', TraceDirection::Both->value));

        // laravel/mcp never validates arguments against the advertised schema,
        // so the enum above is a hint to the client and this is the enforcement.
        if ($traceDirection === null) {
            return Response::error('Direction must be one of: '.implode(', ', array_column(TraceDirection::cases(), 'value')).'.');
        }

        $descriptionBudget = min(
            max((int) $request->integer('descriptions', self::DEFAULT_DESCRIPTIONS), 0),
            self::MAX_DESCRIPTIONS,
        );

        $notesByAlias = ($this->mapReader)();

        if ($notesByAlias === []) {
            return Response::error(MapReader::EMPTY_MAP_MESSAGE);
        }

        $trace = ($this->nodeTrace)($notesByAlias, $alias, $traceDirection);

        if ($trace === null) {
            $text = 'No node ['.$alias.'] on the map — use search-map to find the right alias.';

            $text .= "\n\n".$this->mapReader->freshnessNotice();

            ($this->consultationRecorder)(
                vaultPath: EtruscanConfig::vaultPath(),
                type: UsageEventType::Trace,
                outcome: UsageOutcome::Miss,
                subject: $alias,
                results: 0,
                servedText: $text,
            );

            return Response::text($text);
        }

        $text = $this->render(alias: $alias, trace: $trace, descriptionBudget: $descriptionBudget);
        $fqcn = $notesByAlias[$alias]->frontmatter['fqcn'] ?? null;
        $limit = min(50, max(0, (int) $request->integer('evidence_limit', 20)));

        if ($limit > 0 && is_string($fqcn)) {
            $text .= "\n\n".($this->sourceReferenceTrace)(
                $fqcn,
                $traceDirection,
                max(0, (int) $request->integer('evidence_offset', 0)),
                $limit,
                $this->mapReader->freshnessStatus(),
            );
        }

        $text .= "\n\n".$this->mapReader->freshnessNotice();

        ($this->consultationRecorder)(
            vaultPath: EtruscanConfig::vaultPath(),
            type: UsageEventType::Trace,
            outcome: UsageOutcome::Hit,
            subject: $alias,
            results: count($trace['references']) + count($trace['referencedBy']),
            servedText: $text,
        );

        return Response::text($text);
    }

    /**
     * @param  array{references: array<string, string>, referencedBy: array<string, string>}  $trace
     */
    private function render(string $alias, array $trace, int $descriptionBudget): string
    {
        $lines = ['# '.$alias];

        $lines[] = '';
        $lines[] = 'References (annotated nodes):';
        $lines = array_merge($lines, $this->section($trace['references'], $descriptionBudget));

        $lines[] = '';
        $lines[] = 'Referenced by (annotated nodes):';
        $lines = array_merge($lines, $this->section($trace['referencedBy'], $descriptionBudget));

        $lines[] = '';
        $lines[] = NoteTrustReminder::LINE;

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, string>  $neighbours
     * @return list<string>
     */
    private function section(array $neighbours, int $descriptionBudget): array
    {
        if ($neighbours === []) {
            return ['- (none)'];
        }

        // Budget is spent only on neighbours that have something to say, so a
        // run of undescribed nodes never eats the allowance.
        $describable = count(array_filter($neighbours, static fn (string $description): bool => $description !== ''));

        $lines = [];
        $spent = 0;

        foreach ($neighbours as $alias => $description) {
            $inline = $description !== '' && $spent < $descriptionBudget;

            if ($inline) {
                $spent++;
            }

            $lines[] = '- '.$alias.($inline ? ' — '.$description : '');
        }

        if ($describable > $spent) {
            $lines[] = TruncationNotice::line(
                what: 'neighbour descriptions',
                shown: $spent,
                total: $describable,
                retrieval: 'every alias above is listed in full; call lookup-node on any alias for its description, or raise the descriptions parameter',
            );
        }

        return $lines;
    }
}
