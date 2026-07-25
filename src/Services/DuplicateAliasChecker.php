<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Services;

use WellDigit\Etruscan\Attributes\EtruscanNode;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;
use WellDigit\Etruscan\Enums\CheckCategory;
use WellDigit\Etruscan\Enums\CheckSeverity;
use WellDigit\Etruscan\Payloads\CheckFinding;
use WellDigit\Etruscan\Payloads\ScannedClass;

#[EtruscanNode('duplicate-alias-checker')]
#[EtruscanLayer('service')]
#[EtruscanContext('check')]
final readonly class DuplicateAliasChecker
{
    /**
     * @param  list<ScannedClass>  $scannedClasses
     * @return list<CheckFinding>
     */
    public function __invoke(array $scannedClasses): array
    {
        $fqcnsByAlias = [];

        foreach ($scannedClasses as $scannedClass) {
            if ($scannedClass->alias === null) {
                continue;
            }

            $fqcnsByAlias[$scannedClass->alias][] = $scannedClass->fqcn;
        }

        $findings = [];

        foreach ($fqcnsByAlias as $alias => $fqcns) {
            $fqcns = array_values(array_unique($fqcns));

            if (count($fqcns) < 2) {
                continue;
            }

            $findings[] = new CheckFinding(
                category: CheckCategory::DuplicateAlias,
                severity: CheckSeverity::Error,
                message: sprintf('Alias [%s] is claimed by %d classes: %s', $alias, count($fqcns), implode(', ', $fqcns)),
            );
        }

        return $findings;
    }
}
