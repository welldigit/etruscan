<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use WellDigit\Etruscan\Payloads\NoteContent;
use WellDigit\Etruscan\Services\NotePathResolver;
use WellDigit\Etruscan\Services\VaultWriter;

beforeEach(function () {
    $this->vaultPath = sys_get_temp_dir().'/barkme-vault-'.uniqid();
});

afterEach(function () {
    File::deleteDirectory($this->vaultPath);
});

test('it writes one markdown note per node carrying the generation marker', function () {
    $result = app(VaultWriter::class)(
        vaultPath: $this->vaultPath,
        notes: [
            new NoteContent(alias: 'alpha', frontmatter: ['alias' => 'alpha'], links: ['beta']),
            new NoteContent(alias: 'beta', frontmatter: ['alias' => 'beta'], links: []),
        ],
        notePathResolver: new NotePathResolver(groupByAxisKeys: []),
        markerKey: 'ci-generated',
        markerValue: 'barkme',
    );

    expect($result)->toBe(['written' => 2, 'removed' => 0, 'orphaned' => []])
        ->and(File::exists($this->vaultPath.'/alpha.md'))->toBeTrue()
        ->and(File::exists($this->vaultPath.'/beta.md'))->toBeTrue()
        ->and(File::get($this->vaultPath.'/alpha.md'))->toContain('ci-generated: barkme')
        ->and(File::get($this->vaultPath.'/alpha.md'))->toContain('- [[beta]]');
});

test('stale generated notes without manual notes are removed on the next run', function () {
    File::ensureDirectoryExists($this->vaultPath);
    File::put($this->vaultPath.'/stale.md', "---\nalias: stale\nci-generated: barkme\n---\n");

    $result = app(VaultWriter::class)(
        vaultPath: $this->vaultPath,
        notes: [new NoteContent(alias: 'fresh', frontmatter: ['alias' => 'fresh'], links: [])],
        notePathResolver: new NotePathResolver(groupByAxisKeys: []),
        markerKey: 'ci-generated',
        markerValue: 'barkme',
    );

    expect($result)->toBe(['written' => 1, 'removed' => 1, 'orphaned' => []])
        ->and(File::exists($this->vaultPath.'/stale.md'))->toBeFalse()
        ->and(File::exists($this->vaultPath.'/fresh.md'))->toBeTrue();
});

test('hand-written notes without the marker are never touched', function () {
    File::ensureDirectoryExists($this->vaultPath);
    File::put($this->vaultPath.'/manual.md', "# My own note\n");
    File::put($this->vaultPath.'/frontmatter-but-no-marker.md', "---\ntitle: mine\n---\nBody.\n");

    $result = app(VaultWriter::class)(
        vaultPath: $this->vaultPath,
        notes: [new NoteContent(alias: 'generated', frontmatter: ['alias' => 'generated'], links: [])],
        notePathResolver: new NotePathResolver(groupByAxisKeys: []),
        markerKey: 'ci-generated',
        markerValue: 'barkme',
    );

    expect($result)->toBe(['written' => 1, 'removed' => 0, 'orphaned' => []])
        ->and(File::exists($this->vaultPath.'/manual.md'))->toBeTrue()
        ->and(File::exists($this->vaultPath.'/frontmatter-but-no-marker.md'))->toBeTrue();
});

test('rewriting the same notes is idempotent', function () {
    $notes = [new NoteContent(alias: 'alpha', frontmatter: ['alias' => 'alpha'], links: [])];
    $notePathResolver = new NotePathResolver(groupByAxisKeys: []);

    $vaultWriter = app(VaultWriter::class);
    $vaultWriter(
        vaultPath: $this->vaultPath,
        notes: $notes,
        notePathResolver: $notePathResolver,
        markerKey: 'ci-generated',
        markerValue: 'barkme',
    );
    $firstContent = File::get($this->vaultPath.'/alpha.md');

    $result = $vaultWriter(
        vaultPath: $this->vaultPath,
        notes: $notes,
        notePathResolver: $notePathResolver,
        markerKey: 'ci-generated',
        markerValue: 'barkme',
    );

    expect($result)->toBe(['written' => 1, 'removed' => 0, 'orphaned' => []])
        ->and(File::get($this->vaultPath.'/alpha.md'))->toBe($firstContent);
});

test('the vault directory is created when missing', function () {
    expect(File::exists($this->vaultPath))->toBeFalse();

    app(VaultWriter::class)(
        vaultPath: $this->vaultPath,
        notes: [],
        notePathResolver: new NotePathResolver(groupByAxisKeys: []),
        markerKey: 'ci-generated',
        markerValue: 'barkme',
    );

    expect(File::isDirectory($this->vaultPath))->toBeTrue();
});

test('notes are grouped into axis subfolders and axis-less notes stay at the root', function () {
    $result = app(VaultWriter::class)(
        vaultPath: $this->vaultPath,
        notes: [
            new NoteContent(alias: 'alpha', frontmatter: ['alias' => 'alpha', 'domain' => 'booking'], links: []),
            new NoteContent(alias: 'rootless', frontmatter: ['alias' => 'rootless'], links: []),
        ],
        notePathResolver: new NotePathResolver(groupByAxisKeys: ['domain']),
        markerKey: 'ci-generated',
        markerValue: 'barkme',
    );

    expect($result)->toBe(['written' => 2, 'removed' => 0, 'orphaned' => []])
        ->and(File::exists($this->vaultPath.'/booking/alpha.md'))->toBeTrue()
        ->and(File::exists($this->vaultPath.'/rootless.md'))->toBeTrue();
});

test('a note that moved folders leaves no stale copy and the old folder is pruned', function () {
    File::ensureDirectoryExists($this->vaultPath.'/old-domain');
    File::put($this->vaultPath.'/old-domain/alpha.md', "---\nalias: alpha\nci-generated: barkme\n---\n");

    $result = app(VaultWriter::class)(
        vaultPath: $this->vaultPath,
        notes: [new NoteContent(alias: 'alpha', frontmatter: ['alias' => 'alpha', 'domain' => 'new-domain'], links: [])],
        notePathResolver: new NotePathResolver(groupByAxisKeys: ['domain']),
        markerKey: 'ci-generated',
        markerValue: 'barkme',
    );

    expect($result)->toBe(['written' => 1, 'removed' => 1, 'orphaned' => []])
        ->and(File::exists($this->vaultPath.'/old-domain/alpha.md'))->toBeFalse()
        ->and(File::isDirectory($this->vaultPath.'/old-domain'))->toBeFalse()
        ->and(File::exists($this->vaultPath.'/new-domain/alpha.md'))->toBeTrue();
});

test('a root note moved into a folder loses its stale root copy', function () {
    File::ensureDirectoryExists($this->vaultPath);
    File::put($this->vaultPath.'/alpha.md', "---\nalias: alpha\nci-generated: barkme\n---\n");

    $result = app(VaultWriter::class)(
        vaultPath: $this->vaultPath,
        notes: [new NoteContent(alias: 'alpha', frontmatter: ['alias' => 'alpha', 'domain' => 'booking'], links: [])],
        notePathResolver: new NotePathResolver(groupByAxisKeys: ['domain']),
        markerKey: 'ci-generated',
        markerValue: 'barkme',
    );

    expect($result)->toBe(['written' => 1, 'removed' => 1, 'orphaned' => []])
        ->and(File::exists($this->vaultPath.'/alpha.md'))->toBeFalse()
        ->and(File::exists($this->vaultPath.'/booking/alpha.md'))->toBeTrue();
});

test('hand-written notes inside subfolders survive and their folder is not pruned', function () {
    File::ensureDirectoryExists($this->vaultPath.'/notes');
    File::put($this->vaultPath.'/notes/manual.md', "# My own note\n");

    $result = app(VaultWriter::class)(
        vaultPath: $this->vaultPath,
        notes: [],
        notePathResolver: new NotePathResolver(groupByAxisKeys: ['domain']),
        markerKey: 'ci-generated',
        markerValue: 'barkme',
    );

    expect($result)->toBe(['written' => 0, 'removed' => 0, 'orphaned' => []])
        ->and(File::exists($this->vaultPath.'/notes/manual.md'))->toBeTrue();
});

test('a directory holding only hidden files is not pruned', function () {
    File::ensureDirectoryExists($this->vaultPath.'/keepme');
    File::put($this->vaultPath.'/keepme/.keep', '');

    app(VaultWriter::class)(
        vaultPath: $this->vaultPath,
        notes: [],
        notePathResolver: new NotePathResolver(groupByAxisKeys: []),
        markerKey: 'ci-generated',
        markerValue: 'barkme',
    );

    expect(File::isDirectory($this->vaultPath.'/keepme'))->toBeTrue()
        ->and(File::exists($this->vaultPath.'/keepme/.keep'))->toBeTrue();
});

test('rewriting the same grouped notes is idempotent', function () {
    $notes = [new NoteContent(alias: 'alpha', frontmatter: ['alias' => 'alpha', 'domain' => 'booking'], links: [])];
    $notePathResolver = new NotePathResolver(groupByAxisKeys: ['domain']);

    $vaultWriter = app(VaultWriter::class);
    $vaultWriter(
        vaultPath: $this->vaultPath,
        notes: $notes,
        notePathResolver: $notePathResolver,
        markerKey: 'ci-generated',
        markerValue: 'barkme',
    );
    $firstContent = File::get($this->vaultPath.'/booking/alpha.md');

    $result = $vaultWriter(
        vaultPath: $this->vaultPath,
        notes: $notes,
        notePathResolver: $notePathResolver,
        markerKey: 'ci-generated',
        markerValue: 'barkme',
    );

    expect($result)->toBe(['written' => 1, 'removed' => 0, 'orphaned' => []])
        ->and(File::get($this->vaultPath.'/booking/alpha.md'))->toBe($firstContent);
});

test('manual content added to an existing note survives regeneration', function () {
    $notes = [new NoteContent(alias: 'alpha', frontmatter: ['alias' => 'alpha'], links: [])];
    $notePathResolver = new NotePathResolver(groupByAxisKeys: []);

    $vaultWriter = app(VaultWriter::class);
    $vaultWriter(
        vaultPath: $this->vaultPath,
        notes: $notes,
        notePathResolver: $notePathResolver,
        markerKey: 'ci-generated',
        markerValue: 'barkme',
    );
    File::append($this->vaultPath.'/alpha.md', "\nMy hard-won insight.\n");

    $result = $vaultWriter(
        vaultPath: $this->vaultPath,
        notes: $notes,
        notePathResolver: $notePathResolver,
        markerKey: 'ci-generated',
        markerValue: 'barkme',
    );

    expect($result)->toBe(['written' => 1, 'removed' => 0, 'orphaned' => []])
        ->and(File::get($this->vaultPath.'/alpha.md'))->toContain('My hard-won insight.');
});

test('only the generated blocks are rewritten; the human description and notes are kept', function () {
    File::ensureDirectoryExists($this->vaultPath);
    File::put($this->vaultPath.'/alpha.md', implode("\n", [
        '---',
        'alias: alpha',
        'ci-generated: barkme',
        '---',
        '',
        '## Description',
        '',
        'A human description that regeneration must not touch.',
        '',
        '## References',
        '',
        '- [[beta]]',
        '',
        '## My analysis',
        '',
        'This class is load-bearing.',
    ])."\n");

    app(VaultWriter::class)(
        vaultPath: $this->vaultPath,
        notes: [new NoteContent(alias: 'alpha', frontmatter: ['alias' => 'alpha'], links: ['gamma'])],
        notePathResolver: new NotePathResolver(groupByAxisKeys: []),
        markerKey: 'ci-generated',
        markerValue: 'barkme',
    );
    $content = File::get($this->vaultPath.'/alpha.md');

    expect($content)->toContain('A human description that regeneration must not touch.')
        ->and($content)->toContain('- [[gamma]]')
        ->and($content)->not->toContain('- [[beta]]')
        ->and($content)->toContain("## My analysis\n\nThis class is load-bearing.");
});

test('a human description survives regeneration and stays before the references', function () {
    File::ensureDirectoryExists($this->vaultPath);
    File::put($this->vaultPath.'/alpha.md', implode("\n", [
        '---',
        'alias: alpha',
        'ci-generated: barkme',
        '---',
        '',
        '## Description',
        '',
        'A description written by a human, not a docblock.',
        '',
        '## References',
        '',
        '- [[beta]]',
        '',
        'A trailing manual note.',
    ])."\n");

    app(VaultWriter::class)(
        vaultPath: $this->vaultPath,
        notes: [new NoteContent(alias: 'alpha', frontmatter: ['alias' => 'alpha'], links: ['beta'])],
        notePathResolver: new NotePathResolver(groupByAxisKeys: []),
        markerKey: 'ci-generated',
        markerValue: 'barkme',
    );

    $content = File::get($this->vaultPath.'/alpha.md');

    expect($content)->toContain('A description written by a human, not a docblock.')
        ->and($content)->toContain('A trailing manual note.')
        ->and(strpos($content, 'A description written by a human'))->toBeLessThan((int) strpos($content, '## References'));
});

test('a stale Referenced by section is treated as generated, not carried as manual content', function () {
    File::ensureDirectoryExists($this->vaultPath);
    File::put($this->vaultPath.'/alpha.md', implode("\n", [
        '---',
        'alias: alpha',
        'ci-generated: barkme',
        '---',
        '',
        '## References',
        '',
        '- [[beta]]',
        '',
        '## Referenced by',
        '',
        '- [[gone]]',
        '',
        'A human note.',
    ])."\n");

    app(VaultWriter::class)(
        vaultPath: $this->vaultPath,
        notes: [new NoteContent(alias: 'alpha', frontmatter: ['alias' => 'alpha'], links: ['beta'], referencedBy: ['fresh'])],
        notePathResolver: new NotePathResolver(groupByAxisKeys: []),
        markerKey: 'ci-generated',
        markerValue: 'barkme',
    );

    $content = File::get($this->vaultPath.'/alpha.md');

    expect($content)->toContain('- [[fresh]]')        // the freshly computed backlink
        ->and($content)->not->toContain('[[gone]]')   // the stale backlink is gone, not preserved as manual
        ->and($content)->toContain('A human note.');  // the actual manual content survives
});

test('a long one-line human description is wrapped on write and then stays stable', function () {
    File::ensureDirectoryExists($this->vaultPath);
    $oneLiner = trim(str_repeat('lorem ipsum dolor sit amet ', 12)); // > 100 chars on one line
    File::put($this->vaultPath.'/alpha.md', implode("\n", [
        '---',
        'alias: alpha',
        'ci-generated: barkme',
        '---',
        '',
        '## Description',
        '',
        $oneLiner,
    ])."\n");

    $vaultWriter = app(VaultWriter::class);
    $notes = [new NoteContent(alias: 'alpha', frontmatter: ['alias' => 'alpha'], links: [])];
    $write = fn () => $vaultWriter(
        vaultPath: $this->vaultPath,
        notes: $notes,
        notePathResolver: new NotePathResolver(groupByAxisKeys: []),
        markerKey: 'ci-generated',
        markerValue: 'barkme',
    );

    $write();
    $wrapped = File::get($this->vaultPath.'/alpha.md');
    $write();

    expect($wrapped)->not->toContain($oneLiner)                       // the one long line is gone
        ->and($wrapped)->toContain('lorem ipsum')                     // content preserved
        ->and(File::get($this->vaultPath.'/alpha.md'))->toBe($wrapped); // second run is idempotent
});

test('nothing code-side can overwrite a human description', function () {
    File::ensureDirectoryExists($this->vaultPath);
    File::put($this->vaultPath.'/alpha.md', implode("\n", [
        '---',
        'alias: alpha',
        'ci-generated: barkme',
        '---',
        '',
        '## Description',
        '',
        'Hand-tuned description.',
    ])."\n");

    app(VaultWriter::class)(
        vaultPath: $this->vaultPath,
        notes: [new NoteContent(alias: 'alpha', frontmatter: ['alias' => 'alpha'], links: [], description: 'Code-derived words.')],
        notePathResolver: new NotePathResolver(groupByAxisKeys: []),
        markerKey: 'ci-generated',
        markerValue: 'barkme',
    );

    $content = File::get($this->vaultPath.'/alpha.md');

    expect($content)->toContain('Hand-tuned description.')
        ->and($content)->not->toContain('Code-derived words.');
});

test('an orphan whose only human content is its description is kept and reported', function () {
    File::ensureDirectoryExists($this->vaultPath);
    File::put($this->vaultPath.'/gone.md', implode("\n", [
        '---',
        'alias: gone',
        'ci-generated: barkme',
        '---',
        '',
        '## Description',
        '',
        'Knowledge worth keeping.',
    ])."\n");

    $result = app(VaultWriter::class)(
        vaultPath: $this->vaultPath,
        notes: [],
        notePathResolver: new NotePathResolver(groupByAxisKeys: []),
        markerKey: 'ci-generated',
        markerValue: 'barkme',
    );

    expect($result['orphaned'])->toBe(['gone.md'])
        ->and(File::exists($this->vaultPath.'/gone.md'))->toBeTrue();
});

test('legacy delimiter lines are stripped while the manual content below them is kept', function () {
    File::ensureDirectoryExists($this->vaultPath);
    File::put($this->vaultPath.'/alpha.md', implode("\n", [
        '---',
        'alias: alpha',
        'ci-generated: barkme',
        '---',
        '',
        '%% Manual notes below this line are preserved across regenerations %%',
        '',
        'Old note written under the legacy format.',
    ])."\n");

    app(VaultWriter::class)(
        vaultPath: $this->vaultPath,
        notes: [new NoteContent(alias: 'alpha', frontmatter: ['alias' => 'alpha'], links: [])],
        notePathResolver: new NotePathResolver(groupByAxisKeys: []),
        markerKey: 'ci-generated',
        markerValue: 'barkme',
    );

    $content = File::get($this->vaultPath.'/alpha.md');

    expect($content)->toContain('Old note written under the legacy format.')
        ->and($content)->not->toContain('%% Manual notes below this line');
});

test('manual notes travel with a note that moves into a folder', function () {
    $noteContent = new NoteContent(alias: 'alpha', frontmatter: ['alias' => 'alpha', 'domain' => 'booking'], links: []);

    $vaultWriter = app(VaultWriter::class);
    $vaultWriter(
        vaultPath: $this->vaultPath,
        notes: [$noteContent],
        notePathResolver: new NotePathResolver(groupByAxisKeys: []),
        markerKey: 'ci-generated',
        markerValue: 'barkme',
    );
    File::append($this->vaultPath.'/alpha.md', "\nKeep me.\n");

    $result = $vaultWriter(
        vaultPath: $this->vaultPath,
        notes: [$noteContent],
        notePathResolver: new NotePathResolver(groupByAxisKeys: ['domain']),
        markerKey: 'ci-generated',
        markerValue: 'barkme',
    );

    expect($result)->toBe(['written' => 1, 'removed' => 1, 'orphaned' => []])
        ->and(File::exists($this->vaultPath.'/alpha.md'))->toBeFalse()
        ->and(File::get($this->vaultPath.'/booking/alpha.md'))->toContain('Keep me.');
});

test('an orphaned generated note carrying manual notes is kept and reported', function () {
    File::ensureDirectoryExists($this->vaultPath);
    File::put($this->vaultPath.'/gone.md', implode("\n", [
        '---',
        'alias: gone',
        'ci-generated: barkme',
        '---',
        '',
        'Hard-won knowledge.',
    ])."\n");

    $result = app(VaultWriter::class)(
        vaultPath: $this->vaultPath,
        notes: [],
        notePathResolver: new NotePathResolver(groupByAxisKeys: []),
        markerKey: 'ci-generated',
        markerValue: 'barkme',
    );

    expect($result)->toBe(['written' => 0, 'removed' => 0, 'orphaned' => ['gone.md']])
        ->and(File::exists($this->vaultPath.'/gone.md'))->toBeTrue();
});

test('purging deletes orphaned generated notes even when they carry manual notes', function () {
    File::ensureDirectoryExists($this->vaultPath);
    File::put($this->vaultPath.'/gone.md', implode("\n", [
        '---',
        'alias: gone',
        'ci-generated: barkme',
        '---',
        '',
        'Hard-won knowledge.',
    ])."\n");

    $result = app(VaultWriter::class)(
        vaultPath: $this->vaultPath,
        notes: [],
        notePathResolver: new NotePathResolver(groupByAxisKeys: []),
        markerKey: 'ci-generated',
        markerValue: 'barkme',
        purgeOrphans: true,
    );

    expect($result)->toBe(['written' => 0, 'removed' => 1, 'orphaned' => []])
        ->and(File::exists($this->vaultPath.'/gone.md'))->toBeFalse();
});

test('a hand-indented code block in a description survives regeneration byte-for-byte', function () {
    File::ensureDirectoryExists($this->vaultPath);

    $note = implode("\n", [
        '---',
        'alias: alpha',
        'ci-generated: barkme',
        '---',
        '',
        '## Description',
        '',
        '    $config = [',
        "        'retries' => 3,",
        '    ];',
        '',
        '## References',
        '',
        '- [[beta]]',
    ])."\n";

    File::put($this->vaultPath.'/alpha.md', $note);

    $regenerate = fn () => app(VaultWriter::class)(
        vaultPath: $this->vaultPath,
        notes: [new NoteContent(alias: 'alpha', frontmatter: ['alias' => 'alpha'], links: ['beta'])],
        notePathResolver: new NotePathResolver(groupByAxisKeys: []),
        markerKey: 'ci-generated',
        markerValue: 'barkme',
    );

    $regenerate();
    $afterFirst = File::get($this->vaultPath.'/alpha.md');

    $regenerate();

    expect($afterFirst)->toBe($note)
        ->and(File::get($this->vaultPath.'/alpha.md'))->toBe($note);
});
