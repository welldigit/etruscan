<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Utilities;

use Illuminate\Support\Facades\File;

#[\EtruscanNode('reports-directory-preparer')]
#[\EtruscanLayer('utility')]
#[\EtruscanContext('usage')]
final class ReportsDirectoryPreparer
{
    public static function prepare(string $reportsDirectory): void
    {
        File::ensureDirectoryExists($reportsDirectory);

        $gitignorePath = $reportsDirectory.DIRECTORY_SEPARATOR.'.gitignore';

        if (! File::exists($gitignorePath)) {
            File::put($gitignorePath, "*\n!.gitignore\n");
        }
    }
}
