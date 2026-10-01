<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Services;

use WellDigit\Etruscan\Enums\CheckCategory;
use WellDigit\Etruscan\Enums\CheckSeverity;
use WellDigit\Etruscan\Payloads\CheckFinding;
use WellDigit\Etruscan\Payloads\ScannedClass;

#[\EtruscanNode('vocabulary-checker')]
#[\EtruscanLayer('service')]
#[\EtruscanContext('check')]
final readonly class VocabularyChecker
{
    private const int NEAR_DUPLICATE_DISTANCE = 2;

    private const int NEAR_DUPLICATE_MIN_LENGTH = 5;

    private const int SUGGESTION_DISTANCE = 3;

    /**
     * When an axis is listed in $allowedVocabulary its values are validated
     * against that set (unknown ones are errors); axes left out stay free-form
     * and get fuzzy near-duplicate detection to catch typos.
     *
     * @param  list<ScannedClass>  $scannedClasses
     * @param  array<string, list<string>>  $allowedVocabulary
     * @return list<CheckFinding>
     */
    public function __invoke(array $scannedClasses, array $allowedVocabulary): array
    {
        $findings = [];

        foreach ($this->distinctValuesByAxis($scannedClasses) as $axisKey => $values) {
            $findings = array_merge($findings, isset($allowedVocabulary[$axisKey])
                ? $this->unknownValueFindings(axisKey: $axisKey, allowed: $allowedVocabulary[$axisKey], values: $values)
                : $this->nearDuplicateFindings(axisKey: $axisKey, values: $values));
        }

        return $findings;
    }

    /**
     * @param  list<ScannedClass>  $scannedClasses
     * @return array<string, list<string>>
     */
    private function distinctValuesByAxis(array $scannedClasses): array
    {
        $valuesByAxis = [];

        foreach ($scannedClasses as $scannedClass) {
            foreach ($scannedClass->axes as $axisKey => $values) {
                foreach ($values as $value) {
                    $valuesByAxis[$axisKey][$value] = true;
                }
            }
        }

        return array_map(static function (array $valueSet): array {
            $values = array_keys($valueSet);
            sort($values);

            return $values;
        }, $valuesByAxis);
    }

    /**
     * @param  list<string>  $allowed
     * @param  list<string>  $values
     * @return list<CheckFinding>
     */
    private function unknownValueFindings(string $axisKey, array $allowed, array $values): array
    {
        $findings = [];

        foreach ($values as $value) {
            if (in_array($value, $allowed, true)) {
                continue;
            }

            $suggestion = $this->closest(value: $value, candidates: $allowed);

            $findings[] = new CheckFinding(
                category: CheckCategory::UnknownVocabulary,
                severity: CheckSeverity::Error,
                message: sprintf(
                    'Axis [%s] value [%s] is not in the configured vocabulary%s.',
                    $axisKey,
                    $value,
                    $suggestion !== null ? sprintf(' (did you mean [%s]?)', $suggestion) : '',
                ),
            );
        }

        return $findings;
    }

    /**
     * @param  list<string>  $values
     * @return list<CheckFinding>
     */
    private function nearDuplicateFindings(string $axisKey, array $values): array
    {
        $findings = [];
        $count = count($values);

        for ($first = 0; $first < $count; $first++) {
            for ($second = $first + 1; $second < $count; $second++) {
                $left = $values[$first];
                $right = $values[$second];

                if (min(strlen($left), strlen($right)) < self::NEAR_DUPLICATE_MIN_LENGTH) {
                    continue;
                }

                if (levenshtein($left, $right) > self::NEAR_DUPLICATE_DISTANCE) {
                    continue;
                }

                $findings[] = new CheckFinding(
                    category: CheckCategory::UnknownVocabulary,
                    severity: CheckSeverity::Warning,
                    message: sprintf('Axis [%s] has near-identical values [%s] and [%s] — a typo would fragment the taxonomy.', $axisKey, $left, $right),
                );
            }
        }

        return $findings;
    }

    /**
     * @param  list<string>  $candidates
     */
    private function closest(string $value, array $candidates): ?string
    {
        $closest = null;
        $closestDistance = PHP_INT_MAX;

        foreach ($candidates as $candidate) {
            $distance = levenshtein($value, $candidate);

            if ($distance < $closestDistance) {
                $closestDistance = $distance;
                $closest = $candidate;
            }
        }

        return $closestDistance <= self::SUGGESTION_DISTANCE ? $closest : null;
    }
}
