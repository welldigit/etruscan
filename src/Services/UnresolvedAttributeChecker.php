<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Services;

use WellDigit\Etruscan\Enums\CheckCategory;
use WellDigit\Etruscan\Enums\CheckSeverity;
use WellDigit\Etruscan\Payloads\CheckFinding;
use WellDigit\Etruscan\Payloads\ScannedClass;

#[\EtruscanNode('unresolved-attribute-checker')]
#[\EtruscanLayer('service')]
#[\EtruscanContext('check')]
final readonly class UnresolvedAttributeChecker
{
    public function __construct(private AxisAttributeReader $axisAttributeReader) {}

    /**
     * @param  list<ScannedClass>  $scannedClasses
     * @return list<CheckFinding>
     */
    public function __invoke(array $scannedClasses): array
    {
        $findings = [];

        foreach ($scannedClasses as $scannedClass) {
            $reported = [];

            foreach ($scannedClass->evidence as $evidence) {
                if ($evidence->kind !== 'attribute' || isset($reported[$evidence->target])) {
                    continue;
                }

                if (! $this->axisAttributeReader->isUnresolvedEtruscanAttribute($evidence->target)) {
                    continue;
                }

                $reported[$evidence->target] = true;
                $shortName = class_basename($evidence->target);

                $findings[] = new CheckFinding(
                    category: CheckCategory::UnresolvedAttribute,
                    severity: CheckSeverity::Warning,
                    message: sprintf(
                        '#[%s] on %s (%s:%d) resolves to [%s], which does not exist, so the class stays off the map. Write #[\\%s] or import the attribute.',
                        $shortName,
                        $scannedClass->fqcn,
                        $scannedClass->sourcePath,
                        $evidence->line,
                        $evidence->target,
                        $shortName,
                    ),
                );
            }
        }

        return $findings;
    }
}
