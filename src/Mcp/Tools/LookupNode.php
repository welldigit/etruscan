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
use WellDigit\Etruscan\Mcp\Services\NodeLookup;
use WellDigit\Etruscan\Payloads\ParsedNote;
use WellDigit\Etruscan\Payloads\UsageEvent;
use WellDigit\Etruscan\Services\UsageRecorder;
use WellDigit\Etruscan\Services\VaultReader;
use WellDigit\Etruscan\Utilities\AbsolutePathResolver;
use WellDigit\Etruscan\Utilities\UsageLogPathResolver;

#[IsReadOnly]
#[EtruscanNode('lookup-node')]
#[EtruscanLayer('tool')]
#[EtruscanContext('mcp')]
final class LookupNode extends Tool
{
    protected string $description = 'Read one node of the codebase map in full: description, taxonomy, source file path, dependencies in both directions, and any human notes. Use the source path to open the real file.';

    public function __construct(
        private readonly VaultReader $vaultReader,
        private readonly NodeLookup $nodeLookup,
        private readonly UsageRecorder $usageRecorder,
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

        $vaultPath = AbsolutePathResolver::resolve((string) config('etruscan.vault_path', '.etruscan'));
        $notesByAlias = ($this->vaultReader)($vaultPath, (string) config('etruscan.generated_marker', 'generated_by'));

        if ($notesByAlias === []) {
            return Response::error('The map is empty — generate it first with: php artisan etruscan:generate');
        }

        $lookup = ($this->nodeLookup)($notesByAlias, $alias);

        if ($lookup['note'] === null) {
            $this->record(vaultPath: $vaultPath, subject: $alias, outcome: UsageOutcome::Miss, results: 0);

            $suggestions = $lookup['suggestions'] === []
                ? ''
                : ' Closest aliases: '.implode(', ', $lookup['suggestions']).'.';

            return Response::text('No node ['.$alias.'] on the map.'.$suggestions.' If this class should be on the map, annotate it with #[EtruscanNode] and regenerate.');
        }

        $this->record(vaultPath: $vaultPath, subject: $alias, outcome: UsageOutcome::Hit, results: 1);

        return Response::text($this->render($lookup['note']));
    }

    private function render(ParsedNote $parsedNote): string
    {
        $lines = ['# '.$parsedNote->alias];

        foreach ($parsedNote->frontmatter as $key => $value) {
            if ($key === (string) config('etruscan.generated_marker', 'generated_by')) {
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

    private function record(string $vaultPath, string $subject, UsageOutcome $outcome, int $results): void
    {
        if (! config('etruscan.usage_tracking', true)) {
            return;
        }

        ($this->usageRecorder)(UsageLogPathResolver::resolve($vaultPath), new UsageEvent(
            type: UsageEventType::Lookup,
            outcome: $outcome,
            subject: $subject,
            results: $results,
            recordedAt: now()->toIso8601String(),
        ));
    }
}
