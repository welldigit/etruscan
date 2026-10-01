<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Services;

use Illuminate\Support\Facades\File;
use WellDigit\Etruscan\Enums\CheckCategory;
use WellDigit\Etruscan\Enums\CheckSeverity;
use WellDigit\Etruscan\Payloads\CheckFinding;
use WellDigit\Etruscan\Payloads\NoteContent;

#[\EtruscanNode('broken-link-checker')]
#[\EtruscanLayer('service')]
#[\EtruscanContext('check')]
final readonly class BrokenLinkChecker
{
    /**
     * Scans every vault note for [[wikilinks]] whose target is not a known
     * node alias — these live in manual notes (generated links always resolve)
     * and typically break when an alias is renamed or a node is removed.
     *
     * @param  list<NoteContent>  $notes
     * @return list<CheckFinding>
     */
    public function __invoke(string $vaultPath, array $notes): array
    {
        if (! File::isDirectory($vaultPath)) {
            return [];
        }

        $knownAliases = [];

        foreach ($notes as $noteContent) {
            $knownAliases[$noteContent->alias] = true;
        }

        $findings = [];
        $reported = [];

        foreach (File::allFiles($vaultPath) as $file) {
            if ($file->getExtension() !== 'md') {
                continue;
            }

            $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $file->getRelativePathname());

            if (preg_match_all('/\[\[([^\]]+)\]\]/', File::get($file->getPathname()), $matches) === false) {
                continue;
            }

            foreach ($matches[1] as $rawTarget) {
                // wikilinks may carry a display alias or heading: [[alias|Label]], [[alias#section]]
                $target = trim((string) strtok($rawTarget, '|#'));

                if ($target === '' || isset($knownAliases[$target])) {
                    continue;
                }

                $key = $relativePath.'|'.$target;

                if (isset($reported[$key])) {
                    continue;
                }

                $reported[$key] = true;

                $findings[] = new CheckFinding(
                    category: CheckCategory::BrokenLink,
                    severity: CheckSeverity::Error,
                    message: sprintf('Note [%s] links to [[%s]], which is not a known node.', $relativePath, $target),
                );
            }
        }

        return $findings;
    }
}
