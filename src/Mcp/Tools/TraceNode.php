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
use WellDigit\Etruscan\Mcp\Services\NodeTrace;
use WellDigit\Etruscan\Payloads\UsageEvent;
use WellDigit\Etruscan\Services\UsageRecorder;
use WellDigit\Etruscan\Services\VaultReader;
use WellDigit\Etruscan\Utilities\AbsolutePathResolver;
use WellDigit\Etruscan\Utilities\UsageLogPathResolver;

#[IsReadOnly]
#[EtruscanNode('trace-node')]
#[EtruscanLayer('tool')]
#[EtruscanContext('mcp')]
final class TraceNode extends Tool
{
    protected string $description = 'Follow a node\'s dependency edges one hop: what it references (out), what references it (in), or both. Each neighbour comes with its one-line description.';

    public function __construct(
        private readonly VaultReader $vaultReader,
        private readonly NodeTrace $nodeTrace,
        private readonly UsageRecorder $usageRecorder,
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

        $vaultPath = AbsolutePathResolver::resolve((string) config('etruscan.vault_path', '.etruscan'));
        $notesByAlias = ($this->vaultReader)($vaultPath, (string) config('etruscan.generated_marker', 'generated_by'));

        if ($notesByAlias === []) {
            return Response::error('The map is empty — generate it first with: php artisan etruscan:generate');
        }

        $trace = ($this->nodeTrace)($notesByAlias, $alias, $traceDirection);

        if ($trace === null) {
            $this->record(vaultPath: $vaultPath, subject: $alias, outcome: UsageOutcome::Miss, results: 0);

            return Response::text('No node ['.$alias.'] on the map — use search-map to find the right alias.');
        }

        $this->record(
            vaultPath: $vaultPath,
            subject: $alias,
            outcome: UsageOutcome::Hit,
            results: count($trace['references']) + count($trace['referencedBy']),
        );

        return Response::text($this->render(alias: $alias, trace: $trace));
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

        return implode("\n", array_merge($lines, $this->section($trace['referencedBy'])));
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

    private function record(string $vaultPath, string $subject, UsageOutcome $outcome, int $results): void
    {
        if (! config('etruscan.usage_tracking', true)) {
            return;
        }

        ($this->usageRecorder)(UsageLogPathResolver::resolve($vaultPath), new UsageEvent(
            type: UsageEventType::Trace,
            outcome: $outcome,
            subject: $subject,
            results: $results,
            recordedAt: now()->toIso8601String(),
        ));
    }
}
