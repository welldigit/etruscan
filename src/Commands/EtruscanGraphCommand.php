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
use WellDigit\Etruscan\Services\VaultDescriptionReader;
use WellDigit\Etruscan\Utilities\AbsolutePathResolver;
use WellDigit\Etruscan\Utilities\EtruscanConfig;
use WellDigit\Etruscan\Utilities\ReportsDirectoryPreparer;
use WellDigit\Etruscan\Utilities\ReportsPathResolver;

#[Description('Render the node graph as a self-contained HTML page')]
#[Signature('etruscan:graph
        {--output= : Override the output path (default: .reports/graph.html inside the vault)}')]
#[EtruscanNode('etruscan-graph')]
#[EtruscanLayer('command')]
#[EtruscanContext('cli')]
final class EtruscanGraphCommand extends Command
{
    public function handle(
        CodebaseScanner $codebaseScanner,
        NodeGraphBuilder $nodeGraphBuilder,
        GraphPageRenderer $graphPageRenderer,
        VaultDescriptionReader $vaultDescriptionReader,
    ): int {
        $scannedFolders = EtruscanConfig::scannedFolders();

        $outputOption = $this->option('output');
        $customOutput = is_string($outputOption) && $outputOption !== '';
        $outputPath = $customOutput
            ? AbsolutePathResolver::resolve($outputOption)
            : ReportsPathResolver::resolve(EtruscanConfig::vaultPath(), 'graph.html');

        $this->info('Scanning for attributed classes ...');

        $scannedClasses = $codebaseScanner($scannedFolders);

        try {
            $notes = $nodeGraphBuilder($scannedClasses, $vaultDescriptionReader(
                vaultPath: EtruscanConfig::vaultPath(),
                markerKey: EtruscanConfig::markerKey(),
            ));
        } catch (AliasCollisionException|ReservedAxisKeyException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($customOutput) {
            File::ensureDirectoryExists(dirname($outputPath));
        } else {
            ReportsDirectoryPreparer::prepare(dirname($outputPath));
        }

        File::put($outputPath, $graphPageRenderer($notes));

        $this->info(sprintf('Rendered graph of %d node(s) into %s', count($notes), $outputPath));

        return self::SUCCESS;
    }
}
