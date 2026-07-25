# Etruscan

Project PHP-attribute-annotated classes into a vault of linked markdown notes — a living map of your codebase, regenerated on demand.

Made by [Well Digit](https://welldigit.com) · [etruscan.dev](https://etruscan.dev)

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
```

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

## Configuration

| Key | Env | Default | Meaning |
|-----|-----|---------|---------|
| `roots` | `ETRUSCAN_ROOTS` (comma-separated) | `[base_path('app'), base_path('src')]` | Directories scanned; missing ones are skipped harmlessly |
| `vault_path` | `ETRUSCAN_VAULT` | `base_path('vault')` | Output vault directory |
| `group_by` | `ETRUSCAN_GROUP_BY` | `null` (flat) | Axis key(s) for subfolder grouping; comma-separated list or array nests folders in order |
| `generated_marker` | — | `generated_by` | Frontmatter key marking generated notes |
| `generated_value` | — | `etruscan` | Value stamped under the marker key |

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

## AI code agents (Laravel Boost)

Etruscan ships a [Laravel Boost](https://github.com/laravel/boost) skill and guideline. In apps using Boost, `php artisan boost:update` picks them up automatically:

- the **`etruscan` skill** teaches agents to use the vault as a codebase map — find a class by alias, follow `## References` edges, jump straight to the source file via the `source` frontmatter path, and treat human-written notes below the generated blocks as protected knowledge;
- the **core guideline** makes agents regenerate the vault instead of letting it drift, and annotate new classes so they appear on the map.

The vault becomes shared ground between humans and agents: both read the same map, and manual notes written by either survive every regeneration.

## Development

```bash
composer install
composer test        # Pest (via Orchestra Testbench)
composer phpstan     # PHPStan level 7 + Larastan
composer rector:dry  # automated refactoring check
composer pint:dry    # code style check
composer check       # all of the above
```

Etruscan eats its own cooking: every class in `src/` is annotated, and [vault/](vault) is the package's own vault — regenerate it with `composer vault`, and its graph page with `composer vault:graph`.

## License

MIT. See [LICENSE.md](LICENSE.md). © Petro Lashyn / [Well Digit](https://welldigit.com).
