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
use WellDigit\Etruscan\Mcp\Services\MapSearch;
use WellDigit\Etruscan\Payloads\ParsedNote;
use WellDigit\Etruscan\Payloads\UsageEvent;
use WellDigit\Etruscan\Services\UsageRecorder;
use WellDigit\Etruscan\Services\VaultReader;
use WellDigit\Etruscan\Utilities\AbsolutePathResolver;
use WellDigit\Etruscan\Utilities\UsageLogPathResolver;

#[IsReadOnly]
#[EtruscanNode('search-map')]
#[EtruscanLayer('tool')]
#[EtruscanContext('mcp')]
final class SearchMap extends Tool
{
    protected string $description = 'Search the codebase map by name or concept — matches node aliases, class names, taxonomy values and descriptions. Returns ranked nodes; follow up with lookup-node.';

    public function __construct(
        private readonly VaultReader $vaultReader,
        private readonly MapSearch $mapSearch,
        private readonly UsageRecorder $usageRecorder,
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

        $vaultPath = AbsolutePathResolver::resolve((string) config('etruscan.vault_path', '.etruscan'));
        $notesByAlias = ($this->vaultReader)($vaultPath, (string) config('etruscan.generated_marker', 'generated_by'));

        if ($notesByAlias === []) {
            return Response::error('The map is empty — generate it first with: php artisan etruscan:generate');
        }

        $matches = ($this->mapSearch)($notesByAlias, $query);

        $this->record(
            vaultPath: $vaultPath,
            subject: $query,
            outcome: $matches === [] ? UsageOutcome::Miss : UsageOutcome::Hit,
            results: count($matches),
        );

        if ($matches === []) {
            return Response::text('No nodes match ['.$query.']. The concept may be missing from the map — consider annotating the class that owns it.');
        }

        return Response::text(implode("\n", array_map($this->line(...), $matches)));
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

    private function record(string $vaultPath, string $subject, UsageOutcome $outcome, int $results): void
    {
        if (! config('etruscan.usage_tracking', true)) {
            return;
        }

        ($this->usageRecorder)(UsageLogPathResolver::resolve($vaultPath), new UsageEvent(
            type: UsageEventType::Search,
            outcome: $outcome,
            subject: $subject,
            results: $results,
            recordedAt: now()->toIso8601String(),
        ));
    }
}
