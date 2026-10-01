<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

/**
 * The same two skills ship twice: Laravel Boost discovers them under
 * `resources/boost/skills/`, Claude Code requires them under the plugin's
 * `skills/`. Neither path is negotiable, so the duplication cannot be designed
 * away — only policed, which is how this package handles every rule it cannot
 * enforce structurally.
 *
 * Bodies must match: the instructions an agent follows should not depend on
 * which platform loaded them. Frontmatter may diverge, because Claude Code
 * reads routing fields Boost does not (when_to_use, allowed-tools) — routing is
 * legitimately platform-specific, behaviour is not.
 */
const SKILL_NAMES = ['etruscan-navigate', 'etruscan-annotate'];

function skillBody(string $path): string
{
    $contents = File::get($path);

    // Strip the leading --- ... --- frontmatter block, keep everything after.
    return preg_replace('/\A---\R.*?\R---\R/s', '', $contents) ?? $contents;
}

test('every Boost skill has a plugin counterpart, and vice versa', function () {
    foreach (SKILL_NAMES as $name) {
        expect(File::exists(__DIR__.'/../resources/boost/skills/'.$name.'/SKILL.md'))->toBeTrue($name)
            ->and(File::exists(__DIR__.'/../plugin/skills/'.$name.'/SKILL.md'))->toBeTrue($name);
    }

    $pluginSkills = array_map(
        static fn (string $path): string => basename(dirname($path)),
        File::glob(__DIR__.'/../plugin/skills/*/SKILL.md'),
    );

    sort($pluginSkills);
    $expected = SKILL_NAMES;
    sort($expected);

    expect($pluginSkills)->toBe($expected);
});

test('the instructions do not drift between the Boost and plugin copies', function () {
    foreach (SKILL_NAMES as $name) {
        $boost = skillBody(__DIR__.'/../resources/boost/skills/'.$name.'/SKILL.md');
        $plugin = skillBody(__DIR__.'/../plugin/skills/'.$name.'/SKILL.md');

        expect($plugin)->toBe(
            $boost,
            $name.': the plugin copy has drifted. resources/boost/skills/ is canonical — copy it to plugin/skills/'.$name.'/SKILL.md'
        );
    }
});

// Both ride in context on every request, and Claude Code caps the pair at 1,536
// characters, so a description that grows past it is silently a problem.
test('each skill description fits the budget it is always paying', function () {
    foreach (SKILL_NAMES as $name) {
        $contents = File::get(__DIR__.'/../plugin/skills/'.$name.'/SKILL.md');

        preg_match('/\A---\R(.*?)\R---\R/s', $contents, $matches);
        $frontmatter = $matches[1] ?? '';

        expect($frontmatter)->not->toBe('', $name)
            ->and(mb_strlen($frontmatter))->toBeLessThan(1536, $name);
    }
});

test('the plugin declares the manifest, the server and nothing stale', function () {
    $manifest = decodeJson(File::get(__DIR__.'/../plugin/.claude-plugin/plugin.json'));
    $mcp = decodeJson(File::get(__DIR__.'/../plugin/.mcp.json'));
    $marketplace = decodeJson(File::get(__DIR__.'/../.claude-plugin/marketplace.json'));

    expect(data_get($manifest, 'name'))->toBe('etruscan')
        ->and(data_get($manifest, 'description'))->not->toBe('')
        // The server must run in the consumer's app, not in the plugin directory.
        ->and(data_get($mcp, 'mcpServers.etruscan.args.0'))->toContain('${CLAUDE_PROJECT_DIR}')
        ->and(data_get($mcp, 'mcpServers.etruscan.args.1'))->toBe('etruscan:mcp')
        ->and(data_get($marketplace, 'plugins.0.source'))->toBe('./plugin');
});
