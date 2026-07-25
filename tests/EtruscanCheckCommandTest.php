<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->fixtureDirectory = sys_get_temp_dir().'/etruscan-check-src-'.uniqid();
    $this->vaultPath = sys_get_temp_dir().'/etruscan-check-vault-'.uniqid();

    File::ensureDirectoryExists($this->fixtureDirectory);
    config()->set('etruscan.scanned_folders', [$this->fixtureDirectory]);
    config()->set('etruscan.vault_path', $this->vaultPath);
    config()->set('etruscan.vocabulary', []);
});

afterEach(function () {
    File::deleteDirectory($this->fixtureDirectory);
    File::deleteDirectory($this->vaultPath);
});

test('a well-formed map passes the check', function () {
    File::put($this->fixtureDirectory.'/Alpha.php', <<<'PHP'
        <?php

        namespace Fixture\Check;

        use Fixture\Check\Beta;
        use WellDigit\Etruscan\Attributes\EtruscanNode;
        use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;

        #[EtruscanNode('alpha')]
        #[EtruscanLayer('action')]
        final class Alpha
        {
            public function __construct(private Beta $beta) {}
        }
        PHP);
    File::put($this->fixtureDirectory.'/Beta.php', <<<'PHP'
        <?php

        namespace Fixture\Check;

        use WellDigit\Etruscan\Attributes\EtruscanNode;
        use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;

        #[EtruscanNode('beta')]
        #[EtruscanLayer('model')]
        final class Beta {}
        PHP);

    $this->artisan('etruscan:check')
        ->expectsOutputToContain('No problems found')
        ->assertSuccessful();
});

test('two classes claiming the same alias fail the check', function () {
    File::put($this->fixtureDirectory.'/One.php', <<<'PHP'
        <?php

        namespace Fixture\Check\First;

        use WellDigit\Etruscan\Attributes\EtruscanNode;

        #[EtruscanNode('dup')]
        final class One {}
        PHP);
    File::put($this->fixtureDirectory.'/Two.php', <<<'PHP'
        <?php

        namespace Fixture\Check\Second;

        use WellDigit\Etruscan\Attributes\EtruscanNode;

        #[EtruscanNode('dup')]
        final class Two {}
        PHP);

    $this->artisan('etruscan:check')
        ->expectsOutputToContain('claimed by 2 classes')
        ->assertFailed();
});

test('an off-vocabulary axis value fails when the axis is locked down', function () {
    config()->set('etruscan.vocabulary', ['layer' => ['action', 'model']]);

    File::put($this->fixtureDirectory.'/Typo.php', <<<'PHP'
        <?php

        namespace Fixture\Check;

        use WellDigit\Etruscan\Attributes\EtruscanNode;
        use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;

        #[EtruscanNode('typo')]
        #[EtruscanLayer('serivce')]
        final class Typo {}
        PHP);

    $this->artisan('etruscan:check')
        ->expectsOutputToContain('not in the configured vocabulary')
        ->assertFailed();
});

test('warnings alone pass, but fail under --strict', function () {
    File::put($this->fixtureDirectory.'/Aa.php', <<<'PHP'
        <?php

        namespace Fixture\Check;

        use WellDigit\Etruscan\Attributes\EtruscanNode;
        use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;

        #[EtruscanNode('aa')]
        #[EtruscanLayer('service')]
        final class Aa {}
        PHP);
    File::put($this->fixtureDirectory.'/Bb.php', <<<'PHP'
        <?php

        namespace Fixture\Check;

        use WellDigit\Etruscan\Attributes\EtruscanNode;
        use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;

        #[EtruscanNode('bb')]
        #[EtruscanLayer('serivce')]
        final class Bb {}
        PHP);

    $this->artisan('etruscan:check')->assertSuccessful();
    $this->artisan('etruscan:check', ['--strict' => true])->assertFailed();
});
