<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Services;

use Symfony\Component\Finder\Finder;

#[\EtruscanNode('source-inventory')]
#[\EtruscanLayer('service')]
#[\EtruscanContext('scan')]
final readonly class SourceInventory
{
    /**
     * @param  list<string>  $roots
     * @return array<string, string> Absolute path => content hash; missing roots remain visible.
     */
    public function __invoke(array $roots): array
    {
        $hashes = [];
        $directories = [];

        foreach ($roots as $root) {
            if (is_dir($root)) {
                $directories[] = $root;
            } else {
                $hashes[$root] = 'missing-root';
            }
        }

        if ($directories !== []) {
            foreach (Finder::create()->files()->name('*.php')->in($directories) as $file) {
                $path = $file->getRealPath() ?: $file->getPathname();
                $hashes[$path] = hash('sha256', $file->getContents());
            }
        }

        ksort($hashes);

        return $hashes;
    }
}
