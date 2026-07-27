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
use WellDigit\Etruscan\Mcp\Services\MapOverviewBuilder;
use WellDigit\Etruscan\Payloads\UsageEvent;
use WellDigit\Etruscan\Services\UsageRecorder;
use WellDigit\Etruscan\Services\VaultReader;
use WellDigit\Etruscan\Utilities\AbsolutePathResolver;
use WellDigit\Etruscan\Utilities\UsageLogPathResolver;

#[IsReadOnly]
#[EtruscanNode('map-overview')]
#[EtruscanLayer('tool')]
#[EtruscanContext('mcp')]
final class MapOverview extends Tool
{
    protected string $description = 'Orient in the codebase in one call: every node on the map grouped by a taxonomy axis (context by default). Start here when the area is unfamiliar.';

    public function __construct(
        private readonly VaultReader $vaultReader,
        private readonly MapOverviewBuilder $mapOverviewBuilder,
        private readonly UsageRecorder $usageRecorder,
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

        $vaultPath = AbsolutePathResolver::resolve((string) config('etruscan.vault_path', '.etruscan'));
        $markerKey = (string) config('etruscan.generated_marker', 'generated_by');
        $notesByAlias = ($this->vaultReader)($vaultPath, $markerKey);

        if ($notesByAlias === []) {
            return Response::error('The map is empty — generate it first with: php artisan etruscan:generate');
        }

        $overview = ($this->mapOverviewBuilder)($notesByAlias, $axis, $markerKey);

        $this->record(vaultPath: $vaultPath, subject: $overview['axis'], results: count($notesByAlias));

        $lines = [sprintf('%d nodes on the map, grouped by [%s]:', count($notesByAlias), $overview['axis'])];

        foreach ($overview['groups'] as $value => $aliases) {
            $lines[] = '';
            $lines[] = sprintf('%s (%d):', $value, count($aliases));

            foreach ($aliases as $alias) {
                $lines[] = '- '.$alias;
            }
        }

        return Response::text(implode("\n", $lines));
    }

    private function record(string $vaultPath, string $subject, int $results): void
    {
        if (! config('etruscan.usage_tracking', true)) {
            return;
        }

        ($this->usageRecorder)(UsageLogPathResolver::resolve($vaultPath), new UsageEvent(
            type: UsageEventType::Overview,
            outcome: UsageOutcome::Hit,
            subject: $subject,
            results: $results,
            recordedAt: now()->toIso8601String(),
        ));
    }
}
