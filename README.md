# Etruscan

Project PHP-attribute-annotated classes into a vault of linked markdown notes — a living map of your codebase, regenerated on demand.

Made by [Well Digit](https://welldigit.com) - [etruscan.dev](https://etruscan.dev)

Annotate a class:

```php
use WellDigit\Etruscan\Attributes\EtruscanNode;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;

/**
 * Creates a monitor for the account after guarding the plan cap.
 */
#[EtruscanNode('monitor-create')]
#[EtruscanLayer('action')]
#[EtruscanContext('monitor')]
final readonly class MonitorCreate { /* … */ }
```

Run the projector:

```bash
php artisan etruscan:generate
```

And get one note per node, with class identity, the docblock summary, and `[[wikilinks]]` to every other node the class references:

```markdown
---
alias: monitor-create
class: MonitorCreate
fqcn: Core\Domain\Monitor\Actions\MonitorCreate
source: src/Core/Domain/Monitor/Actions/MonitorCreate.php
layer: action
context: monitor
generated_by: etruscan
---

## Description

Creates a monitor for the account after guarding the plan cap.

## References

- [[monitor]]
- [[monitor-create-data]]

## Referenced by

- [[monitor-store-controller]]
```

References are the nodes a class points at (from real code); Referenced by is the inverse — who points at it — so you can trace a dependency in either direction from the note itself.

## The bundled vocabulary

`#[EtruscanNode('alias')]` marks a class as a node — that part is fixed. Every other dimension is an **axis**, and four are bundled as a sensible default taxonomy:

| Attribute | Frontmatter key | Question it answers | Example values |
|-----------|-----------------|---------------------|----------------|
| `EtruscanLayer` | `layer` | What *kind* of class is this, architecturally? | `action`, `model`, `query`, `data`, `observer`, `policy` |
| `EtruscanDomain` | `domain` | Which *business area* owns it? | `booking`, `invoice`, `monitor` |
| `EtruscanContext` | `context` | Which *bounded context* does it operate in? Repeatable when a class serves several. | `monitor`, `team`, `billing` |
| `EtruscanSlice` | `slice` | Which *vertical feature* does it help deliver, across layers and domains? | `SystemSetup`, `Checkout`, `Onboarding` |

They are defaults, not a closed set. Any subclass of `EtruscanAxis` is an axis — no registration needed:

```php
use Attribute;
use WellDigit\Etruscan\Attributes\EtruscanAxis;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class EtruscanCriticality extends EtruscanAxis {}
```

`#[EtruscanCriticality('high')]` now renders as `criticality: high` — the key is the class short name, lowercased, minus the leading `Etruscan`. Custom keys must not shadow the identity keys (`alias`, `class`, `fqcn`, `extends`, `source`); Etruscan fails loud if they do.

Identity and dimensions are deliberately separate: the **alias is the permanent name** of a node — it keys the file, the wikilinks, and the manual notes — while the **axes are lenses** you can reshape at any time. Swap the whole taxonomy and regenerate: the graph reorganizes around the same stable nodes without losing a note, a link, or a human's words.

## How it works

- **Plain markdown, any editor.** Notes are standard markdown with `[[wikilinks]]` — nothing to install, nothing proprietary. To browse the vault as a linked graph, [Obsidian](https://obsidian.md) is the suggested app.
- **Static analysis only.** The scanner parses your source with nikic/php-parser — nothing is autoloaded or executed, and one unparseable file never breaks the run.
- **Safe regeneration.** Frontmatter and `## References` are always rewritten; `## Description` only when the class has a docblock — a class without one leaves the Description section to you, and it survives every regeneration. Anything else you type in a note survives too and travels with the note when its folder changes. Hand-written notes without the generation marker are never touched.
- **Folder layout by axis.** Set `group_by` (or `--group-by=context`) to project notes into `{vault}/{axisValue}/{alias}.md`. Several keys — comma-separated or a config array — nest folders in order: `--group-by=layer,domain` gives `{vault}/{layer}/{domain}/{alias}.md`, and a note missing an axis simply skips that level. Wikilinks are path-independent, so links keep working in any layout.
- **Orphan care.** A note whose class lost its `#[EtruscanNode]` is deleted only when it contains nothing of yours; otherwise it is kept and reported (`--purge` overrides).

## Installation

```bash
composer require welldigit/etruscan
php artisan vendor:publish --tag=etruscan-config
```

### Install the agent skills into Laravel Boost

If you use [Laravel Boost](https://github.com/laravel/boost), Etruscan's two
skills (`etruscan-annotate`, `etruscan-navigate`) and its guideline live in the
package and are discovered automatically — but you have to pull them into your
agent config with one command:

```bash
php artisan boost:install     # first-time Boost setup — sets up Boost and picks up Etruscan
php artisan boost:update      # already using Boost? just refresh to pick up Etruscan
```

Confirm they landed with `php artisan boost:list-skills` — you should see
`etruscan-annotate` and `etruscan-navigate` sourced from `welldigit/etruscan`.

No Boost, or want the skills without the Composer package at all? Pull them
straight from GitHub instead:

```bash
php artisan boost:add-skill welldigit/etruscan --all
```

## Getting started

Three steps take you from an un-annotated app to a live map.

**1. Align `scanned_folders` with your codebase — the one setting you must get right.**
The scanner only ever looks inside the folders listed in `config/etruscan.php`
(default `['app', 'src']`). If your meaningful classes live elsewhere —
`src/Core/…`, `app/Domain`, `packages/*/src` — set it there, or the scan finds
nothing and the vault comes out empty.

```php
// config/etruscan.php
'scanned_folders' => ['app', 'src'],   // ← edit to match YOUR layout
```

**2. Let a code agent annotate the codebase for you.** Adding `#[EtruscanNode]`
to hundreds of classes by hand is the tedious part — so hand it to a
[Boost](https://github.com/laravel/boost)-aware agent, which ships with the
`etruscan-annotate` skill for exactly this. Paste a prompt like:

> Use the **etruscan-annotate** skill to bootstrap an Etruscan map for this
> project. Read the codebase, then propose the axis taxonomy (layer / domain /
> context / …) and which classes deserve to be nodes — show me the plan as a
> table before touching any files. Once I approve, apply the `#[EtruscanNode]`
> and axis attributes, seed a one-line `## Description` where the intent is
> clear from the code, and flag the classes whose *why* only I can explain.

The agent proposes the schema, you review and tune it, then it writes the
attributes across the codebase. (Already annotated by hand? Skip straight to
step 3.)

**3. Generate the vault.**

```bash
php artisan etruscan:generate          # add --dry-run to preview first
```

Then browse `.etruscan/` in your editor or Obsidian, or open the graph with
`php artisan etruscan:graph`. From here, the `etruscan-navigate` skill lets an
agent read the map instead of crawling files.

## Configuration

| Key | Env | Default | Meaning |
|-----|-----|---------|---------|
| `scanned_folders` | `ETRUSCAN_SCANNED_FOLDERS` (comma-separated) | `['app', 'src']` | Folders scanned, resolved against the app root unless already absolute; missing ones are skipped harmlessly |
| `vault_path` | `ETRUSCAN_VAULT` | `'.etruscan'` | Output vault directory (hidden by default), resolved against the app root unless already absolute |
| `group_by` | `ETRUSCAN_GROUP_BY` | `null` (flat) | Axis key(s) for subfolder grouping; comma-separated list or array nests folders in order |
| `generated_marker` | — | `generated_by` | Frontmatter key marking generated notes |
| `generated_value` | — | `etruscan` | Value stamped under the marker key |
| `vocabulary` | — | `[]` | Optional allowed values per axis; `etruscan:check` flags off-vocabulary values (see below) |

## Command

```bash
php artisan etruscan:generate                     # project using config
php artisan etruscan:generate --dry-run           # report without writing
php artisan etruscan:generate --group-by=domain        # per-run layout override
php artisan etruscan:generate --group-by=layer,domain  # nested folders, one level per axis
php artisan etruscan:generate --group-by=none          # force flat layout
php artisan etruscan:generate --purge             # also delete orphans holding manual notes
```

## Graph view

Render the whole node graph as a single self-contained HTML page — force-directed layout, node colors by any axis, search, and a per-node panel with description, metadata, and inbound/outbound references:

```bash
php artisan etruscan:graph                                   # {vault}/graph.html
php artisan etruscan:graph --output=public/codebase.html     # custom location
```

The page embeds the graph data and has no external dependencies — open the file in any browser, online or offline.

## Checking the map

Aliases are hand-derived and must be globally unique; axis values are free-form strings. Both invite mistakes — two classes colliding on one alias, or a `serivce` typo quietly fragmenting your taxonomy. `etruscan:check` audits for exactly these:

```bash
php artisan etruscan:check            # report problems, exit non-zero on errors
php artisan etruscan:check --strict   # treat warnings as failures too (for CI)
```

It reports four things:

- **Duplicate aliases** — two classes claiming one alias (an error; generation would also fail loud on it).
- **Unknown vocabulary** — for any axis listed in the `vocabulary` config, values outside the allowed set (an error, with the closest match suggested). Axes left free-form instead get a fuzzy warning when two values look like a typo of each other (`service` vs `serivce`).
- **Orphan nodes** — annotated classes with no inbound or outbound links, which often means a missed annotation or a mis-scoped node (a warning).
- **Broken links** — `[[wikilinks]]` in your manual notes pointing at aliases that no longer exist (an error).

Lock an axis down in config once its taxonomy stabilises:

```php
// config/etruscan.php
'vocabulary' => [
    'layer'  => ['action', 'model', 'service', 'query', 'data', 'observer', 'policy'],
    'domain' => ['booking', 'invoice', 'monitor'],
],
```

Errors fail the command (exit 1) so CI can gate on it; warnings only fail under `--strict`.

## AI code agents (Laravel Boost)

Etruscan ships two [Laravel Boost](https://github.com/laravel/boost) skills and a guideline (install them with `php artisan boost:install` / `boost:update` — see [Installation](#install-the-agent-skills-into-laravel-boost)):

- the **`etruscan-annotate` skill** bootstraps the map — point your agent at an un-annotated codebase and it designs the taxonomy (which axes, what values), decides which classes deserve a node, assigns stable aliases, and writes the `#[EtruscanNode]` + axis attributes for you, so you review a plan instead of editing hundreds of files by hand;
- the **`etruscan-navigate` skill** reads the resulting vault as a codebase map — find a class by alias, follow `## References` edges, jump straight to the source file via the `source` frontmatter path, and treat human-written notes below the generated blocks as protected knowledge;
- the **core guideline** makes agents regenerate the vault instead of letting it drift, and annotate new classes so they appear on the map.

One designs the map, the other navigates it. The vault becomes shared ground between humans and agents: both read the same map, and manual notes written by either survive every regeneration.

## Development

```bash
composer install
composer test        # Pest (via Orchestra Testbench)
composer phpstan     # PHPStan level 7 + Larastan
composer rector:dry  # automated refactoring check
composer pint:dry    # code style check
composer check       # all of the above
```

Etruscan consumes its own cooking: every class in `src/` is annotated, and [.etruscan/](.etruscan) is the package's own vault — regenerate it with `composer vault`, and its graph page with `composer vault:graph`. Those scripts pass absolute paths because they run through Testbench, where `base_path()` points at its own skeleton app, not this repo.

## License

MIT. See [LICENSE.md](LICENSE.md). © Petro Lashyn / [Well Digit](https://welldigit.com).
