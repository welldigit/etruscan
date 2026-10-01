<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Services;

use Illuminate\Support\Facades\Log;
use PhpParser\Error;
use PhpParser\ParserFactory;
use Symfony\Component\Finder\Finder;
use WellDigit\Etruscan\Payloads\ScannedClass;

/**
 * @phpstan-import-type ClassFact from ClassFactVisitor
 */
#[\EtruscanNode('codebase-scanner')]
#[\EtruscanLayer('service')]
#[\EtruscanContext('scan')]
final class CodebaseScanner
{
    /** @var list<string> Diagnostics from the most recent scan. */
    public array $issues = [];

    /** @var array<string, string> Hashes of the exact file contents parsed. */
    public array $sourceHashes = [];

    public function __construct(
        private readonly AxisAttributeReader $axisAttributeReader,
        private readonly ClassFactCollector $classFactCollector,
    ) {}

    /**
     * @param  list<string>  $roots  Absolute directories to scan.
     * @return list<ScannedClass>
     */
    public function __invoke(array $roots): array
    {
        $this->issues = [];
        $this->sourceHashes = [];

        foreach ($roots as $root) {
            if (! is_dir($root)) {
                $this->issues[] = 'Missing scan root: '.$root;
            }
        }

        $directories = array_values(array_filter($roots, is_dir(...)));

        if ($directories === []) {
            return [];
        }

        $parser = (new ParserFactory)->createForHostVersion();

        $scannedClasses = [];

        foreach (Finder::create()->files()->name('*.php')->in($directories)->sortByName() as $file) {
            $path = $file->getRealPath() !== false ? $file->getRealPath() : $file->getPathname();

            if (isset($this->sourceHashes[$path])) {
                continue;
            }

            $contents = $file->getContents();
            $this->sourceHashes[$path] = hash('sha256', $contents);

            try {
                $ast = $parser->parse($contents);
            } catch (Error $error) {
                $this->issues[] = 'Unparseable file: '.$path;
                Log::warning(sprintf(
                    'Etruscan: skipping unparseable file %s: %s',
                    $path,
                    $error->getMessage(),
                ));

                continue;
            }

            if ($ast === null) {
                continue;
            }

            $fileFacts = ($this->classFactCollector)($ast);

            foreach ($fileFacts['classes'] as $classFact) {
                $scannedClasses[] = $this->mapToScannedClass(
                    classFact: $classFact,
                    path: $path,
                    references: array_map(static fn ($evidence): string => $evidence->target, $classFact['evidence']),
                );
            }
        }

        return $scannedClasses;
    }

    /**
     * @param  ClassFact  $classFact
     * @param  list<string>  $references
     */
    private function mapToScannedClass(array $classFact, string $path, array $references): ScannedClass
    {
        $alias = null;

        /** @var array<string, list<string>> $axes */
        $axes = [];

        foreach ($classFact['attributes'] as $attribute) {
            if ($this->axisAttributeReader->isNodeAttribute($attribute['fqcn'])) {
                $alias = $attribute['args'][0] ?? $alias;

                continue;
            }

            if (isset($attribute['args'][0]) && $this->axisAttributeReader->isAxisAttribute($attribute['fqcn'])) {
                $axes[$this->axisAttributeReader->resolveAxisKey($attribute['fqcn'])][] = $attribute['args'][0];
            }
        }

        foreach ($axes as $key => $values) {
            $axes[$key] = array_values(array_unique($values));
        }

        return new ScannedClass(
            fqcn: $classFact['fqcn'],
            axes: $axes,
            references: array_values(array_unique($references)),
            sourcePath: $path,
            alias: $alias,
            extendsFqcn: $classFact['extends'],
            evidence: $classFact['evidence'],
        );
    }
}
