# Changelog

All notable changes to `welldigit/etruscan` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Removed

- The isolated-node check from `etruscan:check` — `OrphanNodeChecker` and `CheckCategory::OrphanNode` are gone. It flagged any node with an empty `## References` and `## Referenced by`, but plenty of legitimate leaf classes (show-controllers that only render a view, middleware, framework response classes, console commands) have neither by design, and the checker had no way to tell those apart from a genuinely missed annotation short of wiring in fake dependencies. `etruscan:check` now reports three things instead of four: duplicate aliases, unknown vocabulary, and broken links. Empty references in both directions are allowed. **Breaking** for anyone constructing `OrphanNodeChecker` directly or matching on `CheckCategory::OrphanNode`.

## [1.1.0] - 2026-08-14

### Added

- Context-served measurement: each consultation records the exact size of the answer the map served — counted in characters, not bytes, so em dashes and other multi-byte text do not inflate the figure — and `etruscan:usage` totals it as characters plus an estimated token count (~4 chars/token, honestly labeled an estimate, and defined once in `TokenEstimator`) — in the text report, the `--json` contract (`chars_served`, `estimated_tokens_served`), and a dashboard stat card.
- Boost auto-exposure: when Laravel Boost is installed, the four map tools are appended to `boost.mcp.tools.include`, so they ride the already-connected `laravel-boost` MCP server — no `.mcp.json` edit needed. Verified against `laravel/boost` v2.5 (added to `require-dev`): the tools are both advertised by Boost's server and allowed through its execution gate.
- CI on push and pull request: `composer check` (Rector, Pint, PHPStan, Pest) across both the newest and the lowest resolvable dependency sets, a coverage run gated at 95% (97.3% at time of writing), and `composer audit` failing on advisories against the packages this one ships. A weekly `upstream` workflow runs the suite against `laravel/mcp` `dev-main`, ahead of the `^0.9` constraint, so a v1 incompatibility surfaces here rather than in a consumer's app.

### Changed

- `## Description` is now the human's, unconditionally: the scanner no longer harvests docblock summaries, and generation seeds only the empty `## Description` heading — the slot inviting the words — while the text under it is carried from the note itself, keyed by alias, untouched through every regeneration, taxonomy change, and folder move. A description carrying its own formatting (lists, code fences, headings, tables, indentation) passes through byte-for-byte, opening line included — both the renderer and the parser trim the edges by line rather than by character, so an indented first line is never silently demoted to prose; only pure prose is tidy-wrapped to a readable width. Manual notes are trimmed the same way. Docblocks are ordinary code documentation and never reach the vault; what the map says about a class is exactly what a human (or an agent writing as one, after generation) chose to say. `etruscan:graph` accordingly reads node descriptions from the vault notes — their only home — and the internal `GeneratedNoteSection` enum is renamed `NoteSection`.
- Generated artifacts moved out of the vault root into `{vault}/.reports/` (`graph.html`, `usage.html`, `usage.jsonl`), which is seeded with a self-ignoring `.gitignore` — the vault commits cleanly as pure markdown while machine-local reports stay out of git by default.
- One owner per shared rule, so the surfaces cannot drift apart: `ConsultationRecorder` (the tracking gate, log path, timestamp and answer size, replacing a copy of that logic in each of the four MCP tools), `VaultDescriptionReader` (descriptions read back out of the vault, keeping the graph command thin), `BlankLineTrimmer` (edge-trimming by line for both the renderer and the parser), `TokenEstimator` (the chars-per-token ratio quoted by every report), `EtruscanConfig` (the typed reading of every config key, so a default lives in the published config file and one class instead of being retyped at seventeen call sites) and `MapReader` (the vault read and the empty-map sentence the four tools share).
- An unset or explicitly-null `usage_tracking` now stays on, matching its documented default; only an actual falsy value switches measurement off.
- Static analysis raised from level 7 to **level 9**, now covering `tests/` as well as `src/` — reachable because the config reads are typed at last.
- Development floors corrected to versions that actually build the package: `laravel/pint` `^1.0` → `^1.20` (`^1.0` could not parse `final readonly class` and predated PHP 8.4) and `pestphp/pest` `^4.0` → `^4.7` (earlier releases ship no PHPStan extension). Both were found by running the suite against the declared floor rather than the installed set.
### Removed

- `ScannedClass::$description` and the scanner's docblock-summary extraction. Descriptions are read from the vault, never from code, so the payload no longer carries a field nothing can fill. **Breaking for anyone constructing `ScannedClass` directly** — drop the `description:` argument; this is why the release is a minor rather than a patch. Existing vaults are unaffected: descriptions already written to notes are carried, not re-derived.

## [1.0.3] - 2026-07-27

Documented retroactively: this release was tagged without a changelog entry.

### Added

- Etruscan MCP server (`php artisan etruscan:mcp`, registered as local server `etruscan` via laravel/mcp): four read-only tools — `map-overview` (nodes grouped by a taxonomy axis), `search-map` (ranked matches over aliases, classes, axes and descriptions), `lookup-node` (one full note, ending with the source path), `trace-node` (dependency edges one hop, either direction) — so agents query the map structurally instead of globbing markdown.
- Ground-truth usage measurement: every MCP consultation is appended to `{vault}/usage.jsonl`, including misses — the exact aliases and queries the map could not answer. `php artisan etruscan:usage` (`--days`, `--json`) reports consultations per tool, top nodes, and misses as pre-validated annotation candidates. Disable with `ETRUSCAN_USAGE_TRACKING=false`. (Moved to `{vault}/.reports/` in 1.1.0.)
- Usage dashboard: `etruscan:usage --html` renders the report as a single self-contained page (`--output` overrides) — stat cards, consultations per day with the daily miss count, most-consulted nodes, and annotation candidates.
- MCP hardening: the server is stdio-only, is not registered when `APP_ENV=production`, and recorded subjects are length-capped.

### Changed

- `NoteParser` service: the single owner of note-file parsing (frontmatter, description, links, referenced-by, manual content), shared by the vault writer and the new vault reader.
- Skills and guideline now steer agents to prefer the MCP tools when the server is connected, and `etruscan-annotate` starts from the miss list in `etruscan:usage`.

## [1.0.2] - 2026-07-26

### Changed

- Docs: the Getting started guide now documents the `ETRUSCAN_SCANNED_FOLDERS` and `ETRUSCAN_VAULT` environment variables (with a `.env` example) alongside the `scanned_folders` / `vault_path` config keys, and spells out how relative vs. absolute paths resolve.
- Skills: the Boost skills now make explicit that `## Description` is a durable, human-owned specification — an agent drafts the real business-logic narrative, it is never regenerated when the class has no docblock, and developers enrich it over time — and that the bundled `layer` / `domain` / `context` / `slice` axes are a starting point, not a convention: any `EtruscanAxis` subclass defines a dimension named and valued however the codebase needs.

## [1.0.1] - 2026-07-25

### Added

- `## Referenced by` on every note: the inbound edges (who references a class), precomputed alongside the outbound `## References`, so dependencies trace in both directions from the note itself.
- `etruscan:check` artisan command auditing the map for the mistakes annotations invite — duplicate aliases, off-vocabulary axis values (against an optional `vocabulary` config, with fuzzy typo detection for free-form axes), orphan nodes, and broken `[[wikilinks]]` — with an error/warning split and `--strict` for CI.
- `etruscan-annotate` Boost skill that designs and applies an annotation schema (taxonomy, curation, aliases) to bootstrap a codebase's map — pairing with `etruscan-navigate`, which reads it.

### Changed

- Descriptions reflow-wrap to a readable width (100 columns) as prose — each paragraph packs to even lines with paragraph breaks preserved — so notes stay legible in a plain editor and re-wrap cleanly after a hand-edit; idempotent across regenerations.
- Packaging: `.gitattributes` export-ignores the vault, tests, and dev configs from the Composer dist, so `composer require` downloads only `src/`, `config/`, `resources/`, and the package metadata.

## [1.0.0] - 2026-07-17

### Added

- `#[EtruscanNode('alias')]` identity attribute and the open `EtruscanAxis` vocabulary (`EtruscanLayer`, `EtruscanDomain`, `EtruscanContext`, `EtruscanSlice` — extendable with custom axes).
- Static codebase scanner built on nikic/php-parser: nothing is autoloaded or executed, unparseable files are skipped without failing the run.
- `etruscan:generate` artisan command with `--vault`, `--group-by` (`none` forces flat), `--purge`, and `--dry-run` options.
- One markdown note per node with class identity frontmatter (`alias`, `class`, `fqcn`, `extends`, `source`), a `## Description` section mirroring the class docblock summary, and a `## References` section of `[[wikilinks]]` to referenced nodes (imports, type hints, instantiations, attributes — same-namespace usages included).
- Config-driven folder layout: group notes into `{vault}/{axisValue}/{alias}.md` by any axis key, or nest several levels with a comma-separated list or array (`layer,domain`).
- Configurable scan folders and vault path, both resolved consistently against the application root unless already absolute — whether set via config default, a comma-separated `ETRUSCAN_SCANNED_FOLDERS` / `ETRUSCAN_VAULT` env, or the `--vault`/`--output` CLI options.
- Safe regeneration: only generated blocks are rewritten; manual content survives regeneration and travels with relocated notes; hand-written notes without the generation marker are never touched.
- Human-owned descriptions: when a class has no docblock summary, the note's `## Description` section belongs to the human — it renders before `## References` and survives regeneration; a docblock, when present, always wins.
- Orphan handling: notes whose class lost its `#[EtruscanNode]` are deleted only when pristine, otherwise kept and reported (`--purge` overrides), with empty directories pruned.
- Fail-loud guards: duplicate node aliases, grouping values that cannot become directory names, and custom axes whose key collides with a reserved identity frontmatter key (`alias`, `class`, `fqcn`, `extends`, `source`).
- `etruscan:graph` artisan command rendering the node graph as a single self-contained HTML page: force-directed layout, node colors by any axis, search, and a per-node panel with description, metadata, and inbound/outbound references (`--output` overrides the default `{vault}/graph.html`).
- Laravel Boost integration: the `etruscan-navigate` skill and a core guideline that teach AI code agents to navigate the vault and jump from notes to source files.
