<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Utilities;

use WellDigit\Etruscan\Payloads\NoteContent;
use WellDigit\Etruscan\Services\NotePathResolver;

#[\EtruscanNode('target-folder-counter')]
#[\EtruscanLayer('utility')]
#[\EtruscanContext('vault')]
final class TargetFolderCounter
{
    /**
     * @param  list<NoteContent>  $notes
     */
    public static function count(NotePathResolver $notePathResolver, array $notes): int
    {
        $directories = [];

        foreach ($notes as $noteContent) {
            $directory = dirname($notePathResolver($noteContent));

            if ($directory !== '.') {
                $directories[$directory] = true;
            }
        }

        return count($directories);
    }
}
