# Changelog

All notable changes to `welldigit/etruscan` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-07-17

### Added

- `#[EtruscanNode('alias')]` identity attribute and the open `EtruscanAxis` vocabulary (`EtruscanLayer`, `EtruscanDomain`, `EtruscanContext`, `EtruscanSlice` — extendable with custom axes).
- Static codebase scanner built on nikic/php-parser: nothing is autoloaded or executed, unparseable files are skipped without failing the run.
- `etruscan:generate` artisan command with `--vault`, `--group-by` (`none` forces flat), `--purge`, and `--dry-run` options.
- One markdown note per node with class identity frontmatter (`alias`, `class`, `fqcn`, `extends`, `source`), a `## Description` section mirroring the class docblock summary, and a `## References` section of `[[wikilinks]]` to referenced nodes (imports, type hints, instantiations, attributes — same-namespace usages included).
- Config-driven folder layout: group notes into `{vault}/{axisValue}/{alias}.md` by any axis key, or nest several levels with a comma-separated list or array (`layer,domain`).
- Env-driven scan roots: `ETRUSCAN_ROOTS` (comma-separated paths) overrides the configured `roots` — handy for CI and for generating a package's own vault via Testbench.
- Safe regeneration: only generated blocks are rewritten; manual content survives regeneration and travels with relocated notes; hand-written notes without the generation marker are never touched.
- Human-owned descriptions: when a class has no docblock summary, the note's `## Description` section belongs to the human — it renders before `## References` and survives regeneration; a docblock, when present, always wins.
- Orphan handling: notes whose class lost its `#[EtruscanNode]` are deleted only when pristine, otherwise kept and reported (`--purge` overrides), with empty directories pruned.
- Fail-loud guards: duplicate node aliases, grouping values that cannot become directory names, and custom axes whose key collides with a reserved identity frontmatter key (`alias`, `class`, `fqcn`, `extends`, `source`).
- `etruscan:graph` artisan command rendering the node graph as a single self-contained HTML page: force-directed layout, node colors by any axis, search, and a per-node panel with description, metadata, and inbound/outbound references (`--output` overrides the default `{vault}/graph.html`).
- Laravel Boost integration: the `etruscan` skill and a core guideline teach AI code agents to navigate the vault and jump from notes to source files.
