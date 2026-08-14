<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use WellDigit\Etruscan\Payloads\ScannedClass;
use WellDigit\Etruscan\Services\CodebaseScanner;

beforeEach(function () {
    $this->fixtureDirectory = sys_get_temp_dir().'/barkme-scanner-'.uniqid();
    File::ensureDirectoryExists($this->fixtureDirectory);
});

afterEach(function () {
    File::deleteDirectory($this->fixtureDirectory);
});

test('it scans a class with a node attribute, axes and references', function () {
    File::put($this->fixtureDirectory.'/Alpha.php', <<<'PHP'
        <?php

        namespace Fixture\Inventory;

        use WellDigit\Etruscan\Attributes\EtruscanNode;
        use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
        use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanDomain;
        use Fixture\Inventory\Helper;

        #[EtruscanNode('fixture-alpha')]
        #[EtruscanDomain('monitor')]
        #[EtruscanDomain('monitor')]
        #[EtruscanContext('checking')]
        final class Alpha {}
        PHP);

    $scanned = app(CodebaseScanner::class)([$this->fixtureDirectory]);

    expect($scanned)->toHaveCount(1);

    $class = $scanned[0];

    expect($class)->toBeInstanceOf(ScannedClass::class)
        ->and($class->fqcn)->toBe('Fixture\Inventory\Alpha')
        ->and($class->alias)->toBe('fixture-alpha')
        ->and($class->isNode())->toBeTrue()
        ->and($class->axes)->toBe([
            'domain' => ['monitor'],
            'context' => ['checking'],
        ])
        ->and($class->references)->toContain('Fixture\Inventory\Helper')
        ->and($class->sourcePath)->toEndWith('Alpha.php')
        ->and($class->extendsFqcn)->toBeNull();
});

test('same-namespace usages are collected as references without an import', function () {
    File::put($this->fixtureDirectory.'/Neighbour.php', <<<'PHP'
        <?php

        namespace Fixture\Inventory;

        use WellDigit\Etruscan\Attributes\EtruscanNode;

        #[EtruscanNode('fixture-neighbour')]
        final class Neighbour
        {
            public function __construct(private Helper $helper) {}

            public function build(): Product
            {
                return new Product;
            }
        }
        PHP);

    $scanned = app(CodebaseScanner::class)([$this->fixtureDirectory]);

    expect($scanned[0]->references)->toContain('Fixture\Inventory\Helper')
        ->toContain('Fixture\Inventory\Product');
});

test('a docblock is code documentation, not map input — the scan ignores it', function () {
    File::put($this->fixtureDirectory.'/Documented.php', <<<'PHP'
        <?php

        namespace Fixture\Inventory;

        use WellDigit\Etruscan\Attributes\EtruscanNode;

        /**
         * A summary that must never leak into the vault: descriptions are
         * human-written in the note, not harvested from source.
         */
        #[EtruscanNode('fixture-documented')]
        final class Documented {}
        PHP);

    $scanned = app(CodebaseScanner::class)([$this->fixtureDirectory]);

    // The docblock is simply not collected: ScannedClass has no description
    // property at all, so there is no channel for code to reach the vault.
    expect($scanned[0]->alias)->toBe('fixture-documented');
});

test('the parent class is captured as a resolved fqcn', function () {
    File::put($this->fixtureDirectory.'/Child.php', <<<'PHP'
        <?php

        namespace Fixture\Inventory;

        use WellDigit\Etruscan\Attributes\EtruscanNode;
        use Fixture\Base\AbstractThing;

        #[EtruscanNode('fixture-child')]
        final class Child extends AbstractThing {}
        PHP);

    $scanned = app(CodebaseScanner::class)([$this->fixtureDirectory]);

    expect($scanned[0]->extendsFqcn)->toBe('Fixture\Base\AbstractThing');
});

test('a class without the node attribute is scanned but is not a node', function () {
    File::put($this->fixtureDirectory.'/Plain.php', <<<'PHP'
        <?php

        namespace Fixture\Inventory;

        final class Plain {}
        PHP);

    $scanned = app(CodebaseScanner::class)([$this->fixtureDirectory]);

    expect($scanned)->toHaveCount(1)
        ->and($scanned[0]->fqcn)->toBe('Fixture\Inventory\Plain')
        ->and($scanned[0]->alias)->toBeNull()
        ->and($scanned[0]->isNode())->toBeFalse();
});

test('an unparseable file is skipped without breaking the scan of other files', function () {
    File::put($this->fixtureDirectory.'/Broken.php', '<?php final class {');
    File::put($this->fixtureDirectory.'/Good.php', <<<'PHP'
        <?php

        namespace Fixture\Inventory;

        use WellDigit\Etruscan\Attributes\EtruscanNode;

        #[EtruscanNode('fixture-good')]
        final class Good {}
        PHP);

    $scanned = app(CodebaseScanner::class)([$this->fixtureDirectory]);

    expect($scanned)->toHaveCount(1)
        ->and($scanned[0]->alias)->toBe('fixture-good');
});

test('missing roots are filtered out and yield an empty scan', function () {
    expect(app(CodebaseScanner::class)([$this->fixtureDirectory.'/does-not-exist']))->toBe([]);
});

test('non-string attribute arguments are ignored for axes', function () {
    File::put($this->fixtureDirectory.'/Constants.php', <<<'PHP'
        <?php

        namespace Fixture\Inventory;

        use WellDigit\Etruscan\Attributes\EtruscanNode;
        use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanDomain;

        #[EtruscanNode('fixture-constants')]
        #[EtruscanDomain(Helper::VALUE)]
        final class Constants {}
        PHP);

    $scanned = app(CodebaseScanner::class)([$this->fixtureDirectory]);

    expect($scanned[0]->alias)->toBe('fixture-constants')
        ->and($scanned[0]->axes)->toBe([]);
});
