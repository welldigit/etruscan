<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->fixtureDirectory = sys_get_temp_dir().'/barkme-inventory-src-'.uniqid();
    $this->vaultPath = sys_get_temp_dir().'/barkme-inventory-vault-'.uniqid();

    File::ensureDirectoryExists($this->fixtureDirectory);
    File::put($this->fixtureDirectory.'/Alpha.php', <<<'PHP'
        <?php

        namespace Fixture\Inventory;

        use WellDigit\Etruscan\Attributes\EtruscanNode;
        use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanDomain;
        use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;

        /**
         * Handles the alpha side of the fixture domain.
         */
        #[EtruscanNode('fixture-alpha')]
        #[EtruscanDomain('booking')]
        #[EtruscanLayer('action')]
        final class Alpha {}
        PHP);
    File::put($this->fixtureDirectory.'/Beta.php', <<<'PHP'
        <?php

        namespace Fixture\Inventory;

        use WellDigit\Etruscan\Attributes\EtruscanNode;

        #[EtruscanNode('fixture-beta')]
        final class Beta {}
        PHP);

    config()->set('etruscan.roots', [$this->fixtureDirectory]);
});

afterEach(function () {
    File::deleteDirectory($this->fixtureDirectory);
    File::deleteDirectory($this->vaultPath);
});

test('the configured grouping axis groups notes and leaves axis-less notes at the root', function () {
    config()->set('etruscan.group_by', 'domain');

    $this->artisan('etruscan:generate', ['--vault' => $this->vaultPath])
        ->assertSuccessful();

    expect(File::exists($this->vaultPath.'/booking/fixture-alpha.md'))->toBeTrue()
        ->and(File::exists($this->vaultPath.'/fixture-beta.md'))->toBeTrue()
        ->and(File::get($this->vaultPath.'/booking/fixture-alpha.md'))
        ->toContain("## Description\n\nHandles the alpha side of the fixture domain.");
});

test('the group-by option set to none forces a flat vault over a grouped config', function () {
    config()->set('etruscan.group_by', 'domain');

    $this->artisan('etruscan:generate', ['--vault' => $this->vaultPath, '--group-by' => 'none'])
        ->assertSuccessful();

    expect(File::exists($this->vaultPath.'/fixture-alpha.md'))->toBeTrue()
        ->and(File::directories($this->vaultPath))->toBeEmpty();
});

test('an unseen grouping axis warns and lands every note at the vault root', function () {
    $this->artisan('etruscan:generate', ['--vault' => $this->vaultPath, '--group-by' => 'nonexistent'])
        ->expectsOutputToContain('No scanned node carries the [nonexistent] axis')
        ->assertSuccessful();

    expect(File::exists($this->vaultPath.'/fixture-alpha.md'))->toBeTrue()
        ->and(File::directories($this->vaultPath))->toBeEmpty();
});

test('comma-separated grouping axes nest folders and skip levels a note lacks', function () {
    $this->artisan('etruscan:generate', ['--vault' => $this->vaultPath, '--group-by' => 'layer,domain'])
        ->assertSuccessful();

    expect(File::exists($this->vaultPath.'/action/booking/fixture-alpha.md'))->toBeTrue()
        ->and(File::exists($this->vaultPath.'/fixture-beta.md'))->toBeTrue();
});

test('a config array of axis keys nests folders like the comma-separated option', function () {
    config()->set('etruscan.group_by', ['layer', 'domain']);

    $this->artisan('etruscan:generate', ['--vault' => $this->vaultPath])
        ->assertSuccessful();

    expect(File::exists($this->vaultPath.'/action/booking/fixture-alpha.md'))->toBeTrue();
});

test('a multi-axis dry run names every grouping axis', function () {
    $this->artisan('etruscan:generate', ['--vault' => $this->vaultPath, '--group-by' => 'layer,domain', '--dry-run' => true])
        ->expectsOutputToContain('grouped by layer, domain')
        ->assertSuccessful();

    expect(File::exists($this->vaultPath))->toBeFalse();
});

test('a grouped dry run reports the folder count without writing', function () {
    $this->artisan('etruscan:generate', ['--vault' => $this->vaultPath, '--group-by' => 'domain', '--dry-run' => true])
        ->expectsOutputToContain('grouped by domain')
        ->assertSuccessful();

    expect(File::exists($this->vaultPath))->toBeFalse();
});

test('the group-by option without a value fails loud instead of silently staying flat', function () {
    $this->artisan('etruscan:generate', ['--vault' => $this->vaultPath, '--group-by' => null])
        ->expectsOutputToContain('The --group-by option requires an axis key')
        ->assertFailed();

    expect(File::exists($this->vaultPath))->toBeFalse();
});

test('orphaned notes with manual content are kept and reported until purged', function () {
    File::ensureDirectoryExists($this->vaultPath);
    File::put($this->vaultPath.'/legacy.md', implode("\n", [
        '---',
        'alias: legacy',
        'generated_by: etruscan',
        '---',
        '',
        'Notes I typed by hand.',
    ])."\n");

    $this->artisan('etruscan:generate', ['--vault' => $this->vaultPath])
        ->expectsOutputToContain('orphaned generated note(s) carrying manual notes')
        ->assertSuccessful();

    expect(File::exists($this->vaultPath.'/legacy.md'))->toBeTrue();

    $this->artisan('etruscan:generate', ['--vault' => $this->vaultPath, '--purge' => true])
        ->assertSuccessful();

    expect(File::exists($this->vaultPath.'/legacy.md'))->toBeFalse();
});
