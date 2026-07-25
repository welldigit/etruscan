<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Services;

use Illuminate\Support\Facades\Log;
use PhpParser\Error;
use PhpParser\ParserFactory;
use Symfony\Component\Finder\Finder;
use WellDigit\Etruscan\Attributes\EtruscanNode;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;
use WellDigit\Etruscan\Payloads\ScannedClass;

/**
 * @phpstan-import-type ClassFact from ClassFactVisitor
 */
#[EtruscanNode('codebase-scanner')]
#[EtruscanLayer('service')]
#[EtruscanContext('scan')]
final readonly class CodebaseScanner
{
    public function __construct(
        private AxisAttributeReader $axisAttributeReader,
        private ClassFactCollector $classFactCollector,
    ) {}

    /**
     * @param  list<string>  $roots  Absolute directories to scan.
     * @return list<ScannedClass>
     */
    public function __invoke(array $roots): array
    {
        $directories = array_values(array_filter($roots, is_dir(...)));

        if ($directories === []) {
            return [];
        }

        $parser = (new ParserFactory)->createForHostVersion();

        $scannedClasses = [];

        foreach (Finder::create()->files()->name('*.php')->in($directories) as $file) {
            $path = $file->getRealPath() !== false ? $file->getRealPath() : $file->getPathname();

            try {
                $ast = $parser->parse($file->getContents());
            } catch (Error $error) {
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
                    references: $fileFacts['references'],
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
            description: $classFact['description'],
        );
    }
}
