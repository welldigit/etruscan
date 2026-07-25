<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Services;

use WellDigit\Etruscan\Attributes\EtruscanNode;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;
use WellDigit\Etruscan\Enums\CheckCategory;
use WellDigit\Etruscan\Enums\CheckSeverity;
use WellDigit\Etruscan\Payloads\CheckFinding;
use WellDigit\Etruscan\Payloads\NoteContent;

#[EtruscanNode('orphan-node-checker')]
#[EtruscanLayer('service')]
#[EtruscanContext('check')]
final readonly class OrphanNodeChecker
{
    /**
     * @param  list<NoteContent>  $notes
     * @return list<CheckFinding>
     */
    public function __invoke(array $notes): array
    {
        $findings = [];

        foreach ($notes as $noteContent) {
            if ($noteContent->links !== [] || $noteContent->referencedBy !== []) {
                continue;
            }

            $findings[] = new CheckFinding(
                category: CheckCategory::OrphanNode,
                severity: CheckSeverity::Warning,
                message: sprintf('Node [%s] is isolated — it references nothing and nothing references it.', $noteContent->alias),
            );
        }

        return $findings;
    }
}
