<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Laravel\Mcp\Request;
use WellDigit\Etruscan\Mcp\Services\MapReader;
use WellDigit\Etruscan\Mcp\Services\SourceReferenceTrace;
use WellDigit\Etruscan\Mcp\Tools\LookupNode;
use WellDigit\Etruscan\Mcp\Tools\TraceNode;
use WellDigit\Etruscan\Payloads\ReferenceEvidence;
use WellDigit\Etruscan\Services\CodebaseScanner;
use WellDigit\Etruscan\Services\ReferenceIndex;
use WellDigit\Etruscan\Services\SourceFreshness;
use WellDigit\Etruscan\Services\SourceInventory;
use WellDigit\Etruscan\Services\StructuralMapChecker;

beforeEach(function () {
    $this->fixtureDirectory = sys_get_temp_dir().'/etruscan-evidence-src-'.uniqid();
    $this->vaultPath = sys_get_temp_dir().'/etruscan-evidence-vault-'.uniqid();
    File::ensureDirectoryExists($this->fixtureDirectory);
    config()->set('etruscan.scanned_folders', [$this->fixtureDirectory]);
    config()->set('etruscan.vault_path', $this->vaultPath);
    config()->set('etruscan.usage_tracking', false);
});

afterEach(function () {
    File::deleteDirectory($this->fixtureDirectory);
    File::deleteDirectory($this->vaultPath);
});

function discoveryFixture(string $directory): void
{
    File::put($directory.'/Payment.php', <<<'SOURCE'
        <?php
        namespace Discovery;
        use WellDigit\Etruscan\Attributes\EtruscanNode;
        #[EtruscanNode('payment')]
        final class Payment {}
        SOURCE);
    File::put($directory.'/Caller.php', <<<'SOURCE'
        <?php
        namespace Discovery;
        final class Caller {
            public function run(): Payment { return new Payment; }
        }
        SOURCE);
}

test('usages belong to their class and never leak from imports, neighbours or anonymous bodies', function () {
    File::put($this->fixtureDirectory.'/Several.php', <<<'SOURCE'
        <?php
        namespace Discovery;
        use External\Unused;
        use External\Actual as Renamed;
        use function External\SomeFunction;
        use const External\SOME_CONST;
        class First {
            public function run(): Renamed { return new Renamed; }
            public function nested(): object { return new class { public function run(): Hidden {} }; }
            public function functions(): void { SomeFunction(); echo SOME_CONST; }
        }
        class Second {
            public function run(): Separate { return new Separate; }
        }
        SOURCE);

    $classes = app(CodebaseScanner::class)([$this->fixtureDirectory]);
    expect($classes)->toHaveCount(2)
        ->and($classes[0]->references)->toBe(['External\Actual'])
        ->and($classes[1]->references)->toBe(['Discovery\Separate'])
        ->and($classes[0]->evidence[0]->line)->toBe(8)
        ->and($classes[0]->evidence[0]->kind)->toBe('type')
        ->and($classes[0]->evidence[1]->kind)->toBe('new');
});

test('class evidence distinguishes inheritance, traits, attributes and static usage', function () {
    File::put($this->fixtureDirectory.'/Kinds.php', <<<'SOURCE'
        <?php
        namespace Discovery;
        #[Marker]
        class Example extends Base implements Contract {
            use Shared;
            public function run(Input|Alternative $value): ?Output {
                Utility::run();
                $name = Value::class;
                $matches = $value instanceof Input;
                parent::run();
                return null;
            }
        }
        SOURCE);

    $class = app(CodebaseScanner::class)([$this->fixtureDirectory])[0];
    $kinds = [];
    foreach ($class->evidence as $evidence) {
        $kinds[$evidence->target][] = $evidence->kind;
    }

    expect($kinds['Discovery\Base'])->toBe(['extends', 'static-call'])
        ->and($kinds['Discovery\Contract'])->toBe(['implements'])
        ->and($kinds['Discovery\Marker'])->toBe(['attribute'])
        ->and($kinds['Discovery\Shared'])->toBe(['trait'])
        ->and($kinds['Discovery\Value'])->toBe(['class-constant'])
        ->and($kinds['Discovery\Input'])->toBe(['type', 'instanceof']);
});

test('unannotated callers are discoverable with source evidence without creating extra notes', function () {
    discoveryFixture($this->fixtureDirectory);
    $this->artisan('etruscan:generate')->assertSuccessful();
    $text = (string) app(TraceNode::class)->handle(new Request(['alias' => 'payment', 'direction' => 'in']))->content();

    expect(File::exists($this->vaultPath.'/payment.md'))->toBeTrue()
        ->and(File::exists($this->vaultPath.'/caller.md'))->toBeFalse()
        ->and($text)->toContain('Discovery\Caller (unannotated) -> Discovery\Payment [type]')
        ->toContain('Caller.php:4')
        ->toContain('showing 2 of 2 occurrences')
        ->toContain('Map freshness: current')
        ->toContain('No matches does not prove no impact');
});

test('evidence pages disclose totals and continuation without hiding the rest', function () {
    discoveryFixture($this->fixtureDirectory);
    $this->artisan('etruscan:generate')->assertSuccessful();
    $tool = app(TraceNode::class);
    $first = (string) $tool->handle(new Request(['alias' => 'payment', 'direction' => 'in', 'evidence_limit' => 1]))->content();
    $next = (string) $tool->handle(new Request(['alias' => 'payment', 'direction' => 'in', 'evidence_limit' => 1, 'evidence_offset' => 1]))->content();

    expect($first)->toContain('showing 1 of 2 occurrences')->toContain('evidence_offset=1')
        ->and($next)->toContain('showing 1 of 2 occurrences, offset 1')->not->toContain('More evidence:');
});

test('source changes with unchanged timestamps invalidate evidence and repeated tool calls see the change', function () {
    discoveryFixture($this->fixtureDirectory);
    $this->artisan('etruscan:generate')->assertSuccessful();
    $tool = app(TraceNode::class);
    expect((string) $tool->handle(new Request(['alias' => 'payment']))->content())->toContain('Map freshness: current');

    $source = $this->fixtureDirectory.'/Caller.php';
    $modified = File::lastModified($source);
    File::put($source, '<?php namespace Discovery; final class Caller {}');
    touch($source, $modified);
    $text = (string) $tool->handle(new Request(['alias' => 'payment']))->content();

    expect($text)->toContain('Map freshness: stale')
        ->toContain('Source evidence unavailable (stale)')
        ->not->toContain('Discovery\Caller (unannotated)');
});

test('added and removed files and changed scan roots invalidate the local index', function () {
    discoveryFixture($this->fixtureDirectory);
    $this->artisan('etruscan:generate')->assertSuccessful();
    $freshness = app(SourceFreshness::class);
    File::put($this->fixtureDirectory.'/New.php', '<?php class Added {}');
    expect($freshness($this->vaultPath))->toBe('stale');
    File::delete($this->fixtureDirectory.'/New.php');
    expect($freshness($this->vaultPath))->toBe('current');
    File::delete($this->fixtureDirectory.'/Caller.php');
    expect($freshness($this->vaultPath))->toBe('stale');
    discoveryFixture($this->fixtureDirectory);
    config()->set('etruscan.scanned_folders', []);
    expect($freshness($this->vaultPath))->toBe('stale');
});

test('human prose does not invalidate structural freshness and survives regeneration', function () {
    discoveryFixture($this->fixtureDirectory);
    $this->artisan('etruscan:generate')->assertSuccessful();
    $path = $this->vaultPath.'/payment.md';
    File::put($path, str_replace('## Description', "## Description\n\nRetries reuse the same key.\n\n## Rationale\n\nDuplicate charges must be prevented.\n", File::get($path)));

    expect(app(SourceFreshness::class)($this->vaultPath))->toBe('current');
    $this->artisan('etruscan:check', ['--fresh' => true, '--strict' => true])->assertSuccessful();
    $this->artisan('etruscan:generate')->assertSuccessful();
    expect(File::get($path))->toContain('Retries reuse the same key.')->toContain('Duplicate charges must be prevented.');
});

test('structural checking compares source with notes even without a local index and never writes', function () {
    discoveryFixture($this->fixtureDirectory);
    $this->artisan('etruscan:generate')->assertSuccessful();
    File::delete($this->vaultPath.'/.reports/'.ReferenceIndex::FILE_NAME);
    $this->artisan('etruscan:check', ['--fresh' => true])->assertSuccessful();
    $path = $this->vaultPath.'/payment.md';
    $original = File::get($path);
    File::put($path, str_replace('class: Payment', 'class: Wrong', $original));
    $changed = File::get($path);
    $this->artisan('etruscan:check', ['--fresh' => true])->expectsOutputToContain('Generated structure differs')->assertFailed();
    expect(File::get($path))->toBe($changed)
        ->and(File::exists($this->vaultPath.'/.reports/'.ReferenceIndex::FILE_NAME))->toBeFalse();
});

test('fresh check catches new annotations, changed references and missing notes', function () {
    discoveryFixture($this->fixtureDirectory);
    $this->artisan('etruscan:generate')->assertSuccessful();
    File::put($this->fixtureDirectory.'/Caller.php', <<<'SOURCE'
        <?php namespace Discovery;
        #[\WellDigit\Etruscan\Attributes\EtruscanNode('caller')]
        final class Caller { public function run(): Payment {} }
        SOURCE);
    $this->artisan('etruscan:check', ['--fresh' => true])->assertFailed();
    $this->artisan('etruscan:generate')->assertSuccessful();
    $this->artisan('etruscan:check', ['--fresh' => true])->assertSuccessful();
    File::delete($this->vaultPath.'/caller.md');
    $this->artisan('etruscan:check', ['--fresh' => true])->assertFailed();
});

test('fresh check requires a vault when source has annotated classes', function () {
    discoveryFixture($this->fixtureDirectory);
    $this->artisan('etruscan:check', ['--fresh' => true])->assertFailed();
});

test('unparseable files fail checks and stop generation before changing the existing vault', function () {
    discoveryFixture($this->fixtureDirectory);
    $this->artisan('etruscan:generate')->assertSuccessful();
    $original = File::get($this->vaultPath.'/payment.md');
    $index = File::get($this->vaultPath.'/.reports/'.ReferenceIndex::FILE_NAME);
    File::put($this->fixtureDirectory.'/Payment.php', '<?php class {');
    $this->artisan('etruscan:check')->expectsOutputToContain('Unparseable file:')->assertFailed();
    $this->artisan('etruscan:generate')->expectsOutputToContain('Generation stopped before writing')->assertFailed();
    expect(File::get($this->vaultPath.'/payment.md'))->toBe($original)
        ->and(File::get($this->vaultPath.'/.reports/'.ReferenceIndex::FILE_NAME))->toBe($index);
});

test('missing roots remain explicit and strict checks reject incomplete scans', function () {
    discoveryFixture($this->fixtureDirectory);
    config()->set('etruscan.scanned_folders', [$this->fixtureDirectory, $this->fixtureDirectory.'/missing']);
    $this->artisan('etruscan:generate')->expectsOutputToContain('Missing scan root:')->assertSuccessful();
    expect(app(SourceFreshness::class)($this->vaultPath))->toBe('incomplete');
    $this->artisan('etruscan:check', ['--strict' => true])->assertFailed();
});

test('dry runs do not create a vault or local index', function () {
    discoveryFixture($this->fixtureDirectory);
    $this->artisan('etruscan:generate', ['--dry-run' => true])->assertSuccessful();
    expect(File::exists($this->vaultPath))->toBeFalse();
});

test('damaged local indexes degrade to unknown without breaking map reads', function () {
    discoveryFixture($this->fixtureDirectory);
    $this->artisan('etruscan:generate')->assertSuccessful();
    $path = $this->vaultPath.'/.reports/'.ReferenceIndex::FILE_NAME;
    foreach (['{invalid', '{"version":1,"references":[false]}', 'null'] as $content) {
        File::put($path, $content);
        $text = (string) app(LookupNode::class)->handle(new Request(['alias' => 'payment']))->content();
        expect($text)->toContain('Map freshness: unknown')->toContain('class: Payment');
    }
});

test('regeneration is deterministic and overlapping roots do not duplicate evidence', function () {
    discoveryFixture($this->fixtureDirectory);
    config()->set('etruscan.scanned_folders', [$this->fixtureDirectory, $this->fixtureDirectory]);
    $this->artisan('etruscan:generate')->assertSuccessful();
    $path = $this->vaultPath.'/.reports/'.ReferenceIndex::FILE_NAME;
    $first = File::get($path);
    $this->artisan('etruscan:generate')->assertSuccessful();
    expect(File::get($path))->toBe($first);
    $text = (string) app(TraceNode::class)->handle(new Request(['alias' => 'payment', 'direction' => 'in']))->content();
    expect($text)->toContain('showing 2 of 2 occurrences');
});

arch('architecture: discovery services keep strict types and avoid debug statements')
    ->expect([
        CodebaseScanner::class,
        ReferenceIndex::class,
        SourceFreshness::class,
        SourceInventory::class,
        StructuralMapChecker::class,
        SourceReferenceTrace::class,
        ReferenceEvidence::class,
    ])
    ->toUseStrictTypes()
    ->toBeFinal()
    ->not->toUse(['dd', 'dump', 'var_dump']);

test('a thousand unannotated callers keep evidence responses paginated', function () {
    discoveryFixture($this->fixtureDirectory);
    $source = "<?php namespace Discovery;\n";
    foreach (range(1, 1000) as $number) {
        $source .= 'class Caller'.$number.' { public function run(): Payment {} }'."\n";
    }
    File::put($this->fixtureDirectory.'/Bulk.php', $source);
    $this->artisan('etruscan:generate')->assertSuccessful();
    $text = (string) app(TraceNode::class)->handle(new Request(['alias' => 'payment', 'direction' => 'in']))->content();
    expect($text)->toContain('showing 20 of 1002 occurrences')
        ->toContain('evidence_offset=20')
        ->and(substr_count($text, '(unannotated) ->'))->toBe(20)
        ->and(mb_strlen($text))->toBeLessThan(6000);
});

test('class names resolve case insensitively in generated links', function () {
    File::put($this->fixtureDirectory.'/Case.php', <<<'SOURCE'
        <?php namespace Discovery;
        #[\WellDigit\Etruscan\Attributes\EtruscanNode('payment')]
        class Payment {}
        #[\WellDigit\Etruscan\Attributes\EtruscanNode('caller')]
        class Caller { public function run(): payment {} }
        SOURCE);
    $this->artisan('etruscan:generate')->assertSuccessful();
    expect(File::get($this->vaultPath.'/caller.md'))->toContain('- [[payment]]');
});

test('a retained orphan note is reported beside the status without withholding evidence', function () {
    discoveryFixture($this->fixtureDirectory);
    $this->artisan('etruscan:generate')->assertSuccessful();
    $path = $this->vaultPath.'/payment.md';
    File::put($path, str_replace('## Description', "## Description\n\nRetain this historical decision.", File::get($path)));
    File::delete($this->fixtureDirectory.'/Payment.php');
    $this->artisan('etruscan:generate')->assertSuccessful();
    expect(File::get($path))->toContain('Retain this historical decision.')
        ->and(app(SourceFreshness::class)($this->vaultPath))->toBe('current')
        ->and(app(MapReader::class)->freshnessNotice())->toContain('1 generated note(s) kept for their human words');
    $this->artisan('etruscan:check', ['--fresh' => true, '--strict' => true])->assertFailed();
});

test('a stock app with no src folder and unset roots serves current source evidence', function () {
    $basePath = sys_get_temp_dir().'/etruscan-stock-app-'.uniqid();
    File::ensureDirectoryExists($basePath.'/app');
    discoveryFixture($basePath.'/app');
    $originalBasePath = base_path();
    app()->setBasePath($basePath);
    config()->set('etruscan.scanned_folders', null);

    try {
        $this->artisan('etruscan:generate')->doesntExpectOutputToContain('Missing scan root')->assertSuccessful();

        $text = (string) app(TraceNode::class)->handle(new Request(['alias' => 'payment', 'direction' => 'in']))->content();

        expect(app(SourceFreshness::class)($this->vaultPath))->toBe('current')
            ->and($text)->toContain('Source evidence: showing')
            ->and($text)->toContain('Discovery\Caller (unannotated)');
    } finally {
        app()->setBasePath($originalBasePath);
        File::deleteDirectory($basePath);
    }
});

test('the decoded index is reused until the file is rewritten', function () {
    discoveryFixture($this->fixtureDirectory);
    $this->artisan('etruscan:generate')->assertSuccessful();
    $referenceIndex = app(ReferenceIndex::class);

    $first = $referenceIndex->read($this->vaultPath);
    expect($referenceIndex->lookup($this->vaultPath))->toHaveKey('byTarget.discovery\payment');

    File::delete($this->fixtureDirectory.'/Caller.php');
    $this->artisan('etruscan:generate')->assertSuccessful();

    expect($referenceIndex->read($this->vaultPath))->not->toBe($first)
        ->and($referenceIndex->lookup($this->vaultPath))->not->toHaveKey('byTarget.discovery\payment');
});

test('a stale map does not report an orphan count from the outdated index', function () {
    discoveryFixture($this->fixtureDirectory);
    $this->artisan('etruscan:generate')->assertSuccessful();
    $path = $this->vaultPath.'/payment.md';
    File::put($path, str_replace('## Description', "## Description\n\nRetain this.", File::get($path)));
    File::delete($this->fixtureDirectory.'/Payment.php');
    $this->artisan('etruscan:generate')->assertSuccessful();

    File::put($this->fixtureDirectory.'/Later.php', "<?php\nnamespace Discovery;\nfinal class Later {}\n");

    $mapReader = app(MapReader::class);
    $mapReader();

    expect($mapReader->freshnessStatus())->toBe('stale')
        ->and($mapReader->freshnessNotice())->not->toContain('kept for their human words');
});
