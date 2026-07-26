# Changelog

All notable changes to `welldigit/etruscan` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
