# Etruscan

Mark the classes that matter with PHP attributes. Etruscan projects them into a knowledge graph of plain markdown, committed to the repo: structure derived from real code by static analysis, intent written by humans, both readable by any coding agent in a few hundred tokens instead of a few thousand lines of source.

Made by [Well Digit](https://welldigit.com) — [etruscan.dev](https://etruscan.dev)

## Two kinds of knowledge

A codebase carries two kinds of knowledge, and an agent working in it needs both.

**Structure** — what exists, what references what — lives in the source. Any tool can re-derive it, and agents do, on every task: grep, open, read, repeat. Most of a context window goes to rebuilding a picture the previous task already built and threw away.

**Intent** — which classes matter, what each one is for, why the design took this shape — is not in the source at all. No amount of crawling recovers it. It lives in the heads of the people who wrote the code.

Etruscan puts both into one artifact. Structure is derived by static analysis, so it cannot lie and cannot drift. Intent is declared by humans: an attribute marks the class as worth knowing, and one sentence — written straight into the note, by you or an agent writing as you — says what it is for. The artifact is plain markdown in your repo — diffed in PRs, reviewed like code, regenerated on demand.

The vault is the meeting point of human context and machine context. A person spends one sentence saying what a class is for; every agent that ever works in the repo reads that sentence for a handful of tokens instead of reconstructing an approximation from source. The map makes agents cheaper and sharper at once, and the human words on it survive every regeneration.

## What a node looks like

Annotate a class:

```php
use WellDigit\Etruscan\Attributes\EtruscanNode;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;

#[EtruscanNode('monitor-create')]
#[EtruscanLayer('action')]
#[EtruscanContext('monitor')]
final readonly class MonitorCreate { /* … */ }
```

Run the projector:

```bash
php artisan etruscan:generate
```

Each node becomes one note, with an empty `## Description` already waiting. That slot is the generator's only contribution to the section: it writes the heading, never the words. Fill it with the sentence that matters — it is the manual, human-written part of the map, and regeneration will never touch it.

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

`## References` lists the nodes this class points at, extracted from real code. `## Referenced by` is the inverse — who points at it — so a dependency can be traced in either direction from the note itself.

## Declared, not inferred

Most attempts to give agents a map infer it. Embedding search retrieves whatever looks similar to the question. Auto-derived dependency graphs include every class and therefore rank none. Both answer with guesses.

An Etruscan graph is declared. A person decided this class is a node, gave it a stable name, and said what it is for. That judgment is the signal: information absent from the source, which no tool can re-derive at any price.

Declaration buys determinism.

- **"Who references `monitor-create`?" has an answer.** `## Referenced by` is an inverse index — the exact query grep handles worst, and the first question that matters before changing anything.
- **Retrieval is file reads.** No vector store, no chunking, no similarity thresholds, no reranking, no infrastructure. The graph is markdown in a folder.
- **Traversal is bounded.** A note is ~25 lines: identity, one line of intent, outbound edges, inbound edges. Following five hops costs a few hundred tokens. The crawl it replaces costs a dozen tool calls and thousands of lines of source — per task, every task.
- **Scope is queryable.** Axes slice the graph: hand an agent the `billing` context or the `action` layer instead of the whole repo.

## The contract

Regeneration follows fixed ownership rules. They are the human–agent contract, enforced mechanically:

| Section                             | Owner                              | On regeneration                   |
| ----------------------------------- | ---------------------------------- | --------------------------------- |
| Frontmatter (identity, axes)        | derived from code                  | always rewritten                  |
| `## References`, `## Referenced by` | derived from code                  | always rewritten                  |
| `## Description`                    | human — or an agent writing as one | heading seeded empty; the text never touched, carried by alias |
| Everything else in the note         | human — or an agent writing as one | never touched                     |

Generation seeds the empty `## Description` heading in every note — the slot is the standing invitation — but the words under it are only ever yours, keyed to the alias, the one permanent name. (Plain prose is tidy-wrapped to a readable width — the words never change; a description carrying its own formatting — lists, code fences, headings — passes through byte-for-byte.) Rewrite the class, swap the taxonomy, regroup the folders — the derived sections reshape around your sentence, and your sentence stays. Docblocks are code documentation and never flow into the vault: what the map says about a class is exactly what a human chose to say about it.

A note whose class lost its `#[EtruscanNode]` is deleted only when it contains nothing of yours; otherwise it is kept and reported (`--purge` overrides). Hand-written notes without the generation marker are never touched. Manual text travels with its note when the folder layout changes.

The scanner parses source with nikic/php-parser. Nothing is autoloaded or executed, and one unparseable file never breaks a run.

## If you are an agent

This repository ships its own vault: [`.etruscan/`](.etruscan) is Etruscan's map of Etruscan. In any project that uses the package:

1. **Read the map before crawling source.** Resolve the alias, read the note, follow the edges. Open the source file only when you need implementation detail — `source:` in the frontmatter is the exact path.
2. **Use `## Referenced by` for impact analysis.** Before changing a class, the inbound edges tell you what breaks.
3. **Treat human text as protected.** The `## Description` and everything below the derived sections was written by a person and is the one part of the map you cannot reconstruct. Deliberate improvement is welcome — correcting a description your own change has just invalidated counts, and deserves a mention to the human. Mechanical rewriting and "syncing with code" are not.
4. **Never edit derived sections by hand.** Change the code or the attributes, then run `php artisan etruscan:generate`.
5. **Keep the map complete.** A class without a node is invisible to retrieval. When you add a class that matters, annotate it, regenerate, and fill the empty `## Description` the generator seeds in the new note — as a human would.

With [Laravel Boost](https://github.com/laravel/boost), the `etruscan-navigate` skill encodes 1–4, `etruscan-annotate` encodes 5, and the core guideline keeps regeneration part of your normal working loop. When the `etruscan` MCP server is connected, prefer its tools over raw file reads — `map-overview`, `search-map`, `lookup-node`, `trace-node` — one call each, measured so the team can improve the map (see below).

## Identity and axes

`#[EtruscanNode('alias')]` marks a class as a node — that part is fixed. The **alias is the permanent name** of a node: it keys the file, the wikilinks, and the human notes. Every other dimension is an **axis**, a lens you can reshape at any time. Swap the whole taxonomy and regenerate: the graph reorganizes around the same stable nodes without losing a note, a link, or a human's words.

Four axes ship as a default taxonomy:

| Attribute         | Frontmatter key | Question it answers                                                                 | Example values                                           |
| ----------------- | --------------- | ----------------------------------------------------------------------------------- | -------------------------------------------------------- |
| `EtruscanLayer`   | `layer`         | What *kind* of class is this, architecturally?                                      | `action`, `model`, `query`, `data`, `observer`, `policy` |
| `EtruscanDomain`  | `domain`        | Which *business area* owns it?                                                      | `booking`, `invoice`, `monitor`                          |
| `EtruscanContext` | `context`       | Which *bounded context* does it operate in? Repeatable when a class serves several. | `monitor`, `team`, `billing`                             |
| `EtruscanSlice`   | `slice`         | Which *vertical feature* does it help deliver, across layers and domains?           | `SystemSetup`, `Checkout`, `Onboarding`                  |

They are defaults, not a closed set. Any subclass of `EtruscanAxis` is an axis — no registration needed:

```php
use Attribute;
use WellDigit\Etruscan\Attributes\EtruscanAxis;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class EtruscanCriticality extends EtruscanAxis {}
```

`#[EtruscanCriticality('high')]` now renders as `criticality: high` — the key is the class short name, lowercased, minus the leading `Etruscan`. Custom keys must not shadow the identity keys (`alias`, `class`, `fqcn`, `extends`, `source`); Etruscan fails loud if they do.

## Installation

```bash
composer require welldigit/etruscan
php artisan vendor:publish --tag=etruscan-config
```

### Install the agent skills into Laravel Boost

Etruscan's two skills (`etruscan-annotate`, `etruscan-navigate`) and its guideline live in the package and are discovered by [Laravel Boost](https://github.com/laravel/boost) automatically — pull them into your agent config with one command:

```bash
php artisan boost:install     # first-time Boost setup — sets up Boost and picks up Etruscan
php artisan boost:update      # already using Boost? just refresh to pick up Etruscan
```

Confirm they landed with `php artisan boost:list-skills` — you should see `etruscan-annotate` and `etruscan-navigate` sourced from `welldigit/etruscan`.

No Boost, or want the skills without the Composer package at all? Pull them straight from GitHub:

```bash
php artisan boost:add-skill welldigit/etruscan --all
```

## Getting started

Three steps take you from an un-annotated app to a live map.

**1. Align `scanned_folders` with your codebase — the one setting you must get right.** The scanner only ever looks inside the folders listed in `config/etruscan.php` (default `['app', 'src']`). If your meaningful classes live elsewhere — `src/Core/…`, `app/Domain`, `packages/*/src` — set it there, or the scan finds nothing and the vault comes out empty.

```php
// config/etruscan.php
'scanned_folders' => ['app', 'src'],   // ← edit to match YOUR layout
'vault_path'      => '.etruscan',      // where the notes are written (hidden by default)
```

Both are env-driven, so you can set them in `.env` without publishing the config — `ETRUSCAN_SCANNED_FOLDERS` (comma-separated) overrides the scanned folders, and `ETRUSCAN_VAULT` overrides the vault path:

```dotenv
ETRUSCAN_SCANNED_FOLDERS=app,src,packages/acme/src
ETRUSCAN_VAULT=.etruscan
```

Relative paths resolve against the application root; absolute paths are used as-is.

**2. Let an agent annotate the codebase for you.** Adding `#[EtruscanNode]` to hundreds of classes by hand is the tedious part — so hand it to a Boost-aware agent, which ships with the `etruscan-annotate` skill for exactly this. Paste a prompt like:

> Use the **etruscan-annotate** skill to bootstrap an Etruscan map for this
> project. Read the codebase, then propose the axis taxonomy (layer / domain /
> context / …) and which classes deserve to be nodes — show me the plan as a
> table before touching any files. Once I approve, apply the `#[EtruscanNode]`
> and axis attributes, generate the vault, then fill each note's empty
> `## Description` with a first pass where the intent is clear from the code,
> and flag the classes whose *why* only I can explain.

The agent proposes the schema, you review and tune it, then it writes the attributes across the codebase and seeds the notes. Note the last clause of the prompt: the descriptions are yours — the agent only ever writes them the way you would, into the notes, where regeneration never touches them. It seeds what the code reveals and flags what it cannot know; the classes it flags are where your one sentence of intent is worth the most. (Already annotated by hand? Skip straight to step 3.)

**3. Generate the vault.**

```bash
php artisan etruscan:generate          # add --dry-run to preview first
```

Browse `.etruscan/` in any editor, or render the graph with `php artisan etruscan:graph`. From here, the `etruscan-navigate` skill lets an agent read the map instead of crawling files.

**4. Connect the map to your agent (MCP).**
Using [Laravel Boost](https://github.com/laravel/boost)? **Nothing to do** — Etruscan exposes its four map tools inside the `laravel-boost` MCP server your agent already connects to; verify with `php artisan boost:mcp` being present in `.mcp.json`. Not using Boost? Add Etruscan's own server to your agent's MCP config:

```json
{ "mcpServers": { "etruscan": { "command": "php", "args": ["artisan", "etruscan:mcp"] } } }
```

Without either, agents still work — they read the vault files directly — but consultations are not measured.

## Command

```bash
php artisan etruscan:generate                     # project using config
php artisan etruscan:generate --dry-run           # report without writing
php artisan etruscan:generate --group-by=domain        # per-run layout override
php artisan etruscan:generate --group-by=layer,domain  # nested folders, one level per axis
php artisan etruscan:generate --group-by=none          # force flat layout
php artisan etruscan:generate --purge             # also delete orphans holding human words
```

## Configuration

| Key                | Env                                          | Default          | Meaning                                                                                                     |
| ------------------ | -------------------------------------------- | ---------------- | ----------------------------------------------------------------------------------------------------------- |
| `scanned_folders`  | `ETRUSCAN_SCANNED_FOLDERS` (comma-separated) | `['app', 'src']` | Folders scanned, resolved against the app root unless already absolute; missing ones are skipped harmlessly |
| `vault_path`       | `ETRUSCAN_VAULT`                             | `'.etruscan'`    | Output vault directory (hidden by default), resolved against the app root unless already absolute           |
| `group_by`         | `ETRUSCAN_GROUP_BY`                          | `null` (flat)    | Axis key(s) for subfolder grouping; comma-separated list or array nests folders in order                    |
| `generated_marker` | —                                            | `generated_by`   | Frontmatter key marking generated notes                                                                     |
| `generated_value`  | —                                            | `etruscan`       | Value stamped under the marker key                                                                          |
| `vocabulary`       | —                                            | `[]`             | Optional allowed values per axis; `etruscan:check` flags off-vocabulary values (see below)                  |
| `usage_tracking`   | `ETRUSCAN_USAGE_TRACKING`                    | `true`           | Record MCP map consultations to `{vault}/.reports/usage.jsonl` for `etruscan:usage`; `.reports/` is git-ignored by default, so the log stays on this machine unless you deliberately commit it |

Folder layout is a projection, not structure: `group_by` writes notes into `{vault}/{axisValue}/{alias}.md`, several keys nest folders in order, and a note missing an axis simply skips that level. Wikilinks are path-independent, so links keep working in any layout.

## Graph view

Render the whole node graph as a single self-contained HTML page — force-directed layout, node colors by any axis, search, and a per-node panel with description, metadata, and inbound/outbound references:

```bash
php artisan etruscan:graph                                   # {vault}/.reports/graph.html
php artisan etruscan:graph --output=public/codebase.html     # custom location
```

The page embeds the graph data and has no external dependencies. Open it in any browser, online or offline, and share it with anyone — reading the map requires no repo access and no tooling. Node descriptions on the page are read from the vault notes — their only home — so generate the vault before rendering the graph.

## Checking the map

Aliases are hand-derived and must be globally unique; axis values are free-form strings. Both invite mistakes — two classes colliding on one alias, or a `serivce` typo quietly fragmenting your taxonomy. `etruscan:check` audits for exactly these:

```bash
php artisan etruscan:check            # report problems, exit non-zero on errors
php artisan etruscan:check --strict   # treat warnings as failures too (for CI)
```

It reports three things:

- **Duplicate aliases** — two classes claiming one alias (an error; generation would also fail loud on it).
- **Unknown vocabulary** — for any axis listed in the `vocabulary` config, values outside the allowed set (an error, with the closest match suggested). Axes left free-form instead get a fuzzy warning when two values look like a typo of each other (`service` vs `serivce`).
- **Broken links** — `[[wikilinks]]` in your manual notes pointing at aliases that no longer exist (an error).

A node with no inbound or outbound links is not flagged: plenty of legitimate leaf classes — show-controllers that only render a view, middleware, framework response classes, console commands — have neither, and there is no way to tell those apart from a genuinely missed annotation short of hand-wiring fake dependencies. Empty `## References` and `## Referenced by` are allowed.

Lock an axis down in config once its taxonomy stabilises:

```php
// config/etruscan.php
'vocabulary' => [
    'layer'  => ['action', 'model', 'service', 'query', 'data', 'observer', 'policy'],
    'domain' => ['booking', 'invoice', 'monitor'],
],
```

Errors fail the command (exit 1) so CI can gate on it; warnings only fail under `--strict`.

## MCP server

Etruscan ships its own MCP server, so agents query the map through structured tools instead of globbing markdown — one call replaces a search-plus-read round trip:

| Tool           | What it answers                                                                    |
| -------------- | ---------------------------------------------------------------------------------- |
| `map-overview` | "What is this codebase made of?" — every node grouped by a taxonomy axis           |
| `search-map`   | "Where is X handled?" — ranked matches over aliases, classes, axes and descriptions |
| `lookup-node`  | "Tell me about this class" — the full note, ending with the source path to open    |
| `trace-node`   | "What does it use / who uses it?" — one dependency hop with descriptions           |

**With Laravel Boost, connection is automatic**: Etruscan registers its tools in Boost's `boost.mcp.tools.include`, so they are served by the `laravel-boost` MCP server your agent already connects to — no `.mcp.json` change. Without Boost, add Etruscan's own server to your agent's MCP config (for Claude Code, `.mcp.json` in the project root):

```json
{
  "mcpServers": {
    "etruscan": { "command": "php", "args": ["artisan", "etruscan:mcp"] }
  }
}
```

or `claude mcp add etruscan -- php artisan etruscan:mcp`. Either way the tools read the vault on disk, so regenerate after changing annotated classes and they answer from the fresh map.

**Security posture:** the server speaks stdio only — it is not an HTTP route, opens no port, and runs solely when someone with shell access starts it. All four tools are read-only over the vault's markdown (aliases are looked up, never used as file paths), the only write is the local usage log, and the server is not registered at all when `APP_ENV=production`.

## Measuring usefulness

Because agents reach the map through the MCP tools, usage is measured **server-side, as ground truth** — no honor system, no agent cooperation required. Every consultation is appended to `{vault}/.reports/usage.jsonl` — and `.reports/` ships its own `.gitignore`, so the log never leaves your machine unless you deliberately commit it. Then:

```bash
php artisan etruscan:usage             # the report
php artisan etruscan:usage --days=7    # recent window
php artisan etruscan:usage --json      # machine-readable, for CI or dashboards
php artisan etruscan:usage --html      # self-contained dashboard → {vault}/.reports/usage.html (--output overrides)
```

The `--html` dashboard is a single offline page like the graph view: stat cards, consultations per day with misses in red (watch them fall as you annotate), most-consulted nodes, and the annotation-candidate list — shareable with anyone, no tooling required.

The report shows what the log proves: consultations per tool, the most-consulted nodes (the ones whose descriptions earn the most polish), and — the most valuable signal — **misses**: the exact aliases and queries agents asked for that the map could not answer. Misses are pre-validated annotation candidates; feed them to the `etruscan-annotate` skill and watch them disappear from the next report. That trend — miss velocity falling toward zero — is the honest benchmark of the map paying off.

The report also totals the context the map actually served: characters per consultation, summed and shown as an estimated token count (~4 chars/token — an estimate, since every agent tokenizes differently). It measures what the map delivered, not the agent's total context, but it makes the trade concrete: a few hundred tokens of curated map instead of the file-globbing round-trips it replaced.

What this measures: every consultation through the MCP tools. What it cannot see: agents reading vault files directly (the skills steer them to the tools for exactly this reason). Disable recording with `ETRUSCAN_USAGE_TRACKING=false`; the tools keep answering either way. For a shared team log, edit `.etruscan/.reports/.gitignore` to un-ignore `usage.jsonl` (edit it — a deleted file is reseeded) and add `.etruscan/.reports/usage.jsonl merge=union` to `.gitattributes` so parallel branches merge cleanly.

## Development

```bash
composer install
composer test        # Pest (via Orchestra Testbench)
composer phpstan     # PHPStan level 7 + Larastan
composer rector:dry  # automated refactoring check
composer pint:dry    # code style check
composer check       # all of the above
```

Etruscan consumes its own cooking: every class in `src/` is annotated, and [`.etruscan/`](.etruscan) is the package's own vault — regenerate it with `composer vault`, and its graph page with `composer vault:graph`. Those scripts pass absolute paths because they run through Testbench, where `base_path()` points at its own skeleton app, not this repo.

## License

MIT. See [LICENSE.md](LICENSE.md). © Petro Lashyn / [Well Digit](https://welldigit.com).