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
use WellDigit\Etruscan\Enums\TraceDirection;
use WellDigit\Etruscan\Enums\UsageEventType;
use WellDigit\Etruscan\Enums\UsageOutcome;
use WellDigit\Etruscan\Mcp\Services\ConsultationRecorder;
use WellDigit\Etruscan\Mcp\Services\MapReader;
use WellDigit\Etruscan\Mcp\Services\NodeTrace;
use WellDigit\Etruscan\Utilities\EtruscanConfig;

#[IsReadOnly]
#[EtruscanNode('trace-node')]
#[EtruscanLayer('tool')]
#[EtruscanContext('mcp')]
final class TraceNode extends Tool
{
    protected string $description = 'Follow a node\'s dependency edges one hop: what it references (out), what references it (in), or both. Each neighbour comes with its one-line description.';

    public function __construct(
        private readonly MapReader $mapReader,
        private readonly NodeTrace $nodeTrace,
        private readonly ConsultationRecorder $consultationRecorder,
    ) {}

    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function schema(JsonSchema $schema): array
    {
        return [
            'alias' => $schema->string()->description('The node alias to trace from.')->required(),
            'direction' => $schema->string()->description('out (what it uses), in (who uses it) or both. Default: both.'),
        ];
    }

    public function handle(Request $request): Response
    {
        $alias = trim((string) $request->string('alias'));

        if ($alias === '') {
            return Response::error('Provide the node alias to trace.');
        }

        $traceDirection = TraceDirection::tryFrom((string) $request->string('direction', TraceDirection::Both->value));

        if ($traceDirection === null) {
            return Response::error('Direction must be one of: out, in, both.');
        }

        $notesByAlias = ($this->mapReader)();

        if ($notesByAlias === []) {
            return Response::error(MapReader::EMPTY_MAP_MESSAGE);
        }

        $trace = ($this->nodeTrace)($notesByAlias, $alias, $traceDirection);

        if ($trace === null) {
            $text = 'No node ['.$alias.'] on the map — use search-map to find the right alias.';

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

        $text = $this->render(alias: $alias, trace: $trace);

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
    private function render(string $alias, array $trace): string
    {
        $lines = ['# '.$alias];

        $lines[] = '';
        $lines[] = 'References (what it uses):';
        $lines = array_merge($lines, $this->section($trace['references']));

        $lines[] = '';
        $lines[] = 'Referenced by (who uses it):';
        $lines = array_merge($lines, $this->section($trace['referencedBy']));

        $lines[] = '';
        $lines[] = NoteTrustReminder::LINE;

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, string>  $neighbours
     * @return list<string>
     */
    private function section(array $neighbours): array
    {
        if ($neighbours === []) {
            return ['- (none)'];
        }

        $lines = [];

        foreach ($neighbours as $alias => $description) {
            $lines[] = '- '.$alias.($description === '' ? '' : ' — '.$description);
        }

        return $lines;
    }
}
