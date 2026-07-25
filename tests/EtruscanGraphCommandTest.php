<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->fixtureDirectory = sys_get_temp_dir().'/etruscan-graph-src-'.uniqid();
    $this->vaultPath = sys_get_temp_dir().'/etruscan-graph-vault-'.uniqid();

    File::ensureDirectoryExists($this->fixtureDirectory);
    File::put($this->fixtureDirectory.'/Alpha.php', <<<'PHP'
        <?php

        namespace Fixture\Graph;

        use Fixture\Graph\Beta;
        use WellDigit\Etruscan\Attributes\EtruscanNode;
        use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;

        /**
         * Coordinates the alpha side of the fixture.
         */
        #[EtruscanNode('graph-alpha')]
        #[EtruscanLayer('action')]
        final class Alpha {}
        PHP);
    File::put($this->fixtureDirectory.'/Beta.php', <<<'PHP'
        <?php

        namespace Fixture\Graph;

        use WellDigit\Etruscan\Attributes\EtruscanNode;

        #[EtruscanNode('graph-beta')]
        final class Beta {}
        PHP);

    config()->set('etruscan.scanned_folders', [$this->fixtureDirectory]);
    config()->set('etruscan.vault_path', $this->vaultPath);
});

afterEach(function () {
    File::deleteDirectory($this->fixtureDirectory);
    File::deleteDirectory($this->vaultPath);
});

test('the graph page lands in the vault by default with nodes and edges embedded', function () {
    $this->artisan('etruscan:graph')->assertSuccessful();

    $html = File::get($this->vaultPath.'/graph.html');

    expect($html)->toContain('"id":"graph-alpha"')
        ->toContain('"source":"graph-alpha","target":"graph-beta"')
        ->toContain('"layer":"action"');
});

test('the output option overrides the page location', function () {
    $outputPath = $this->vaultPath.'/nested/codebase.html';

    $this->artisan('etruscan:graph', ['--output' => $outputPath])->assertSuccessful();

    expect(File::exists($outputPath))->toBeTrue()
        ->and(File::exists($this->vaultPath.'/graph.html'))->toBeFalse();
});
