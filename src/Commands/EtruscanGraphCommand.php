<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use WellDigit\Etruscan\Attributes\EtruscanNode;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;
use WellDigit\Etruscan\Exceptions\AliasCollisionException;
use WellDigit\Etruscan\Exceptions\ReservedAxisKeyException;
use WellDigit\Etruscan\Services\CodebaseScanner;
use WellDigit\Etruscan\Services\GraphPageRenderer;
use WellDigit\Etruscan\Services\NodeGraphBuilder;
use WellDigit\Etruscan\Utilities\AbsolutePathResolver;
use WellDigit\Etruscan\Utilities\ScannedFolderResolver;

#[Description('Render the node graph as a self-contained HTML page')]
#[Signature('etruscan:graph
        {--output= : Override the output path (default: graph.html inside the vault)}')]
#[EtruscanNode('etruscan-graph')]
#[EtruscanLayer('command')]
#[EtruscanContext('cli')]
final class EtruscanGraphCommand extends Command
{
    public function handle(
        CodebaseScanner $codebaseScanner,
        NodeGraphBuilder $nodeGraphBuilder,
        GraphPageRenderer $graphPageRenderer,
    ): int {
        $scannedFolders = ScannedFolderResolver::resolve(config('etruscan.scanned_folders', ['app', 'src']));

        $outputOption = $this->option('output');
        $outputPath = AbsolutePathResolver::resolve(
            is_string($outputOption) && $outputOption !== ''
                ? $outputOption
                : ((string) config('etruscan.vault_path', '.etruscan')).DIRECTORY_SEPARATOR.'graph.html',
        );

        $this->info('Scanning for attributed classes ...');

        $scannedClasses = $codebaseScanner($scannedFolders);

        try {
            $notes = $nodeGraphBuilder($scannedClasses);
        } catch (AliasCollisionException|ReservedAxisKeyException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        File::ensureDirectoryExists(dirname($outputPath));
        File::put($outputPath, $graphPageRenderer($notes));

        $this->info(sprintf('Rendered graph of %d node(s) into %s', count($notes), $outputPath));

        return self::SUCCESS;
    }
}
