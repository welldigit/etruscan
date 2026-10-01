<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Services;

use WellDigit\Etruscan\Enums\CheckCategory;
use WellDigit\Etruscan\Enums\CheckSeverity;
use WellDigit\Etruscan\Payloads\CheckFinding;
use WellDigit\Etruscan\Payloads\NoteContent;
use WellDigit\Etruscan\Utilities\EtruscanConfig;

#[\EtruscanNode('structural-map-checker')]
#[\EtruscanLayer('service')]
#[\EtruscanContext('check')]
final readonly class StructuralMapChecker
{
    public function __construct(private VaultReader $vaultReader) {}

    /**
     * @param  list<NoteContent>  $expected
     * @return list<CheckFinding>
     */
    public function __invoke(string $vaultPath, array $expected): array
    {
        $actual = ($this->vaultReader)($vaultPath, EtruscanConfig::markerKey());
        $findings = [];

        foreach ($expected as $note) {
            $stored = $actual[$note->alias] ?? null;
            $frontmatter = $stored->frontmatter ?? [];
            unset($frontmatter[EtruscanConfig::markerKey()]);

            if ($stored === null || ($stored->frontmatter[EtruscanConfig::markerKey()] ?? null) !== EtruscanConfig::markerValue()
                || $frontmatter != $note->frontmatter
                || $stored->links !== $note->links || $stored->referencedBy !== $note->referencedBy) {
                $findings[] = new CheckFinding(
                    CheckCategory::StaleMap,
                    CheckSeverity::Error,
                    'Generated structure differs for ['.$note->alias.'] — run php artisan etruscan:generate. Human text is preserved.',
                );
            }

            unset($actual[$note->alias]);
        }

        foreach ($actual as $alias => $note) {
            $findings[] = new CheckFinding(
                CheckCategory::StaleMap,
                CheckSeverity::Warning,
                'Note ['.$alias.'] has no current annotated class; inspect retained human knowledge before removing it.',
            );
        }

        return $findings;
    }
}
