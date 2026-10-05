# Changelog

All notable changes to `welldigit/etruscan` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.4.0] - 2026-10-05

### Changed

- **laravel/mcp 1.x support.** The requirement widens to `^0.9|^1.0`, so a consumer can install `laravel/ai` v1 (which conflicts with `laravel/mcp` below 1.0) next to Etruscan. The map tools and server run unchanged on both lines; the suite passes against `laravel/mcp` v1.0.1. The dev requirement on `laravel/boost` moves to `^2.10`, the first line that accepts `laravel/mcp` 1.x.

## [1.3.0] - 2026-10-01

Three threads ship together.

**The surface that reaches a model, audited.** Checked against current guidance on working with Claude models and spending context: the thesis and the derivation pipeline held up, the serving layer and the agent-facing text did not, and are fixed below.

**The map, moved to where it pays.** The map's content is stable and small, which is what a cached prompt prefix is built for, while tool results arrive in the volatile tail one round trip at a time.

**Source evidence you can trust the age of.** `trace-node` now carries paginated source evidence, unannotated callers included, from a local index guarded by content-based freshness — and a stock Laravel app gets it out of the box.

### Added

- A local, git-ignored source-reference index generated alongside the curated vault. Named unannotated classes participate without acquiring notes. Trace responses include paginated usage evidence with kinds and source locations, totals, and continuation offsets.
- Content-based freshness in MCP map responses. Changes to scanned PHP, scan roots, generation markers, or generated note structure invalidate the local snapshot. Stale, incomplete, and unavailable source evidence is withheld explicitly.
- `etruscan:check --fresh --strict` compares current source-derived metadata and links against notes without writing or requiring a local index. Missing roots are reported; unparseable files fail checks and stop generation before writes.
- **Annotations without imports.** The bundled attributes now also ship as global classes, so a class joins the map with `#[\EtruscanNode('alias')]` and no `use` line — likewise `#[\EtruscanLayer]`, `#[\EtruscanDomain]`, `#[\EtruscanContext]` and `#[\EtruscanSlice]`. They are real classes loaded through a Composer classmap, not runtime aliases, so IDEs, PHPStan and Larastan resolve them without a helper file. The namespaced classes are unchanged, both spellings scan identically, and axis keys, notes and vaults are untouched.
- **An `unresolved-attribute` check**, for the one mistake the zero-import form invites. An Etruscan-named attribute that resolves to no class — what a missing backslash or import leaves behind — is reported by `etruscan:check` with its file and line, and warned about during `etruscan:generate`. PHP accepts such an attribute in silence, so until now the class simply never reached the map and nothing said so. A warning, promoted to a failure by `--strict`.
- **`etruscan:export` — the map as one markdown index, for an agent's `CLAUDE.md`.** Every node on a single line (alias, source path, description clipped to 140 characters) grouped by the configured `group_by` axis. Imported with one `@.etruscan/map.md` line, it is expanded at session start and sits in the prompt prefix: read at cache rates, present before the first question, costing no tool call. On this package's own 78-node map it is ~18 KB — about a third of the notes it indexes, and roughly a ninth of the source behind them. The tools remain how you go deeper; the index is how you decide where.
- The index is written to the **vault root, not `.reports/`**. That folder seeds a self-ignoring `.gitignore` so machine-local artifacts stay out of git, which is exactly backwards for the one artifact a consumer should commit and have travel with the repo. `--stdout` prints instead, `--output=` relocates.
- **Deterministic by construction** — no timestamp, nothing that moves on its own. Re-exporting an unchanged map rewrites identical bytes, because an index that churns dirties every diff and invalidates the prefix cache it exists to fill. Pinned by a test.
- **`etruscan:check` gains `stale-digest`**, a warning when the exported index is older than the notes it indexes. A stale index is worse than none: it is in context from the first token of every session and is believed. A warning rather than an error, since exporting is opt-in; `--strict` promotes it for CI.
- **A Claude Code plugin** under `plugin/`, with this repo as its own marketplace — `/plugin marketplace add welldigit/etruscan` then `/plugin install etruscan@welldigit`. It registers both skills and connects the map tools by running `php artisan etruscan:mcp` in the consumer's project via `${CLAUDE_PROJECT_DIR}`, with no `.mcp.json` to write. Laravel Boost remains the zero-setup path for Laravel projects; this is the native one for Claude Code, and neither is now a second-class citizen.
- `NodeSummaryLine` — one owner for the node line that `search-map` and the exported index both render, so a reader who meets the same node on both surfaces meets the same sentence. `SearchMap` now defers to it rather than holding its own copy of the format and the 140-character limit.
- **Bounded, self-describing responses.** Every tool that can truncate now says what it held back and the exact call that fetches the rest, in one shared sentence (`TruncationNotice`) so the tools cannot drift into different stories. Silent truncation is worse than a long reply: the reader cannot tell a complete answer from a clipped one.
- `tests/Mcp/ResponseBoundsTest.php` — a deliberately hostile vault (a 200-edge hub, a group far past the member limit, a 50 KB pasted human-notes section) asserting every tool stays under its ceiling and returns valid UTF-8. It fails when someone re-inflates a response, which no substring assertion can do.
- `etruscan.chars_per_token`, with the calibration recipe in the config file. The estimate was hard-coded at 4 chars/token — the familiar figure for English prose, applied to payloads dense with kebab aliases, backslashed FQCNs and paths, all of which tokenize worse. The number the package used to justify itself read optimistically for exactly the content it serves; it is now correctable without a code change, and every surface still quotes one figure.
- **The counterfactual, at last.** `etruscan:usage` now prints the map footprint — what the notes cost in characters against the characters of source they stand in for — and the always-on tool surface, the description text in context on every request whether or not a tool is called. The log could only ever measure what the map delivered; what reading the code instead would have cost is the half the package's claim rests on, and it was never being measured. Both appear in the `--json` contract under `footprint`.

### Changed

- References belong to their containing named class; unrelated classes and anonymous bodies no longer leak edges into neighbours. Unused imports and function/constant names no longer create class links. PHP class-name matching is case-insensitive.
- Export staleness compares content rather than filesystem timestamps.
- Navigation guidance distinguishes static evidence from runtime behavior and search misses from missing annotations. Human prose remains protected and does not invalidate structural freshness.
- Every agent-facing surface teaches the zero-import form: the README, the Boost guideline, both skills, and the miss message from `lookup-node`. This package annotates itself the same way, which drops the edge every node carried to `etruscan-node`, `etruscan-layer` and `etruscan-context` — those edges only ever said "is annotated", and trace and lookup payloads are smaller without them.

- **Scan roots default to what exists.** Left unset, `scanned_folders` scans whichever of `app/` and `src/` are present, so a stock Laravel app no longer reports a missing `src/`, no longer has its scan marked incomplete, and gets source evidence out of the box. An explicit list stays strict.
- **Retained orphan notes no longer withhold evidence.** A generated note kept for its human words is reported beside the freshness status with a count; `incomplete` now means only that an explicitly configured root is missing.
- **`search-map` matches words, not one substring.** `-`, `_`, `\`, `/`, `.` and `:` count as spaces, and every word must match somewhere on a note, so `monitor create` finds `monitor-create`. The generation marker is no longer searchable as an axis.
- **`etruscan:generate` refreshes an existing `{vault}/map.md`**, so the committed index no longer drifts between exports.
- Map tools parse the vault once per call (the freshness structure hash reuses the read) and decode the local index once per request, with evidence looked up by FQCN instead of scanned row by row.
- Notes and the exported index are written atomically (write to a temp file, then rename), so a tool reading mid-generation never sees a half-written note.
- The usage log rotates to `usage.jsonl.1` at 5 MB; `etruscan:usage` reads both generations.
- The `unknown` freshness notice says that `etruscan:generate` is safe on a fresh checkout.
- **The framing moved from substitution to prior, everywhere it appears.** "Consult the map BEFORE crawling source" was written against models that crawled badly; current models crawl well, and a rule that reads as *instead of reading the code* will keep losing arguments with a model that is good at reading code. What the map durably offers is not a replacement for the source but the end of guessing which source to open — and that stays true however good the crawler gets. So the navigate skill is now titled "let the map tell you which code to read", the Boost guideline leads with the same, the MCP server instructions say the map "is an index of the code, never a stand-in for it — open the source it points at", and the README's worst line ("lets an agent read the map instead of crawling files") is gone. The usage report's "standing in for N chars of source" becomes "indexing"; the `--json` field `source_to_note_ratio` keeps its name, because renaming a documented contract for a copy fix is not worth the break.
- One sentence deliberately **not** reframed: the README's claim that a human's description is read "instead of reconstructing an approximation from source". For intent, substitution is the honest claim — that is the half of the map that is genuinely not in the code.
- The skills now ship twice, to `resources/boost/skills/` and `plugin/skills/`, because neither platform's discovery path is negotiable. `tests/PluginSkillParityTest.php` asserts the instruction bodies stay byte-identical while allowing frontmatter to diverge — Claude Code reads routing fields Boost does not, and routing is legitimately platform-specific where behaviour is not.
- **`trace-node` budgets prose, never edges.** Every neighbour alias is still listed — a trace answers "what is the blast radius", and a truncated blast radius is not a smaller answer but a wrong one — while descriptions are inlined for the first 8 per direction, tunable per call with the new `descriptions` argument (`0` for aliases alone). On this repo's own map a hub trace fell from 18,143 characters to roughly 5,500 with every edge intact; the median trace is untouched.
- **`map-overview` leads with counts.** A group past 25 members prints its size and how to expand it rather than its members, and the new `group` argument lists one group in full. Group count grows far more slowly than node count, so the reply stays roughly flat as the map grows where listing every alias was linear. Nothing changes on a map this size — the largest group here is 24 — which is the correct behaviour for a small vault. Where a multi-valued axis makes the group counts exceed the node count, the reply now says why instead of leaving the arithmetic unexplained.
- **`map-overview` stops substituting an axis silently.** Asking for an axis no node carries still falls back to the first one present — good default-tolerant behaviour — but the reply now names the axis it used and the axes that exist, instead of answering a different question in the same shape.
- **`trace-node`'s `direction` is an enum in the advertised schema**, with its error message built from `TraceDirection::cases()` rather than a second hand-maintained copy of the same three values. The runtime check stays: `laravel/mcp` never validates arguments against `inputSchema`, so the enum is a hint to the client and the check is the enforcement. The advertised schema is now covered by tests, which it was not before.
- **`lookup-node` clips only the human-notes section**, on a line boundary, naming the note file that holds the rest. The description and both dependency lists come back whole — the first is a written-once specification and the reason the tool exists, the second is the blast radius.
- **All four tool descriptions rewritten to state their actual contracts** — what each returns and does not return, what is capped, what is never truncated, how it fails, and when not to reach for it. This makes the always-on surface *larger*, which is the right direction: a tool description is sent once per session and is the only place a model learns what a call will cost, so it is the cheapest possible place to carry a standing rule. The corresponding numbers are interpolated from the constants that enforce them, so the description cannot drift from the behaviour.
- **The MCP server instructions no longer name the four tools in prose.** The real tool list is already in context, the roster was a lossy paraphrase of it, and it dangles in both directions — under Boost the instructions are dropped while the tools remain. What only the author knows stays: what the map is, that it comes before crawling source, the trust rule, and the local-recording disclosure.
- **The trust footer keeps the cue and drops the lesson.** It was re-teaching the claim-class rule on every note served while a standing copy sat in the server instructions, the guideline and the navigate skill. Now 105 bytes instead of 187 — roughly 29% of a median `lookup-node` reply recovered — and it points at the note just served rather than restating what a description is. See the provenance note on 1.2.0: whether the footer is load-bearing at all on current models is still untested, and the removal experiment is the honest next step.
- **Both skill descriptions name intent categories instead of enumerating example phrasings.** Frontmatter descriptions ride in every request, and a list of near-synonymous queries taxes every one of them while generalising worse than the categories it spells out — in the navigate skill the enumeration was already subsumed by the clause immediately after it. The capitalised routing words stay: trigger text is allowed its urgency.

### Fixed

- **A clipped search description could return nothing at all.** `SearchMap` cut descriptions to 140 *bytes* with `substr`, so a cut landing inside a multi-byte character — an em dash at the wrong offset — produced invalid UTF-8; `json_encode` then returns `false`, and `laravel/mcp`'s `json_encode(...) ?: ''` turns that into a zero-length JSON-RPC frame. The tool call returned nothing, and raised nothing. Clipping now goes through `TextClipper`, multi-byte throughout, with the ellipsis spent from inside the budget so the limit is a true ceiling. Of this repo's own 69 descriptions, 55 exceeded 140 bytes and 17 were already losing characters to byte-counting; the encoding survived on luck.
- **A note holding invalid bytes could return nothing, by the same route.** Fixing the clip closed the cause this package controls; it did not close the door. A note that arrives already carrying an invalid byte — a bad paste, or a file another tool wrote as latin-1 — flowed through `File::get()` into an MCP response untouched and collapsed the frame identically. Notes are now scrubbed where they enter, in `VaultReader`, and the offending file is named in the log instead of one bad byte silently swallowing a whole answer. Valid text, em dashes included, is passed through byte-for-byte.
- **The core guideline told agents to import axis attributes from the wrong namespace.** `WellDigit\Etruscan\Attributes` holds `EtruscanNode`; the axes live in `WellDigit\Etruscan\Attributes\Vocabulary`. An agent following the guideline wrote a fatal `use`. The navigate skill carried the same error; the annotate skill and README had it right.
- **The guideline's MCP instruction was gated on a condition that is false in the package's own zero-setup path.** It read "when the `etruscan` MCP server is connected" — but under Boost the tools ride `laravel-boost`, and no server by that name exists. Read literally, it routed agents back to file reads in exactly the configuration the README recommends. Both phrasings now describe either path; the same correction landed at `README.md:109`.
- **`search-map`'s only example taught a query the matcher cannot serve.** `MapSearch` matches the whole query as one case-insensitive substring, with no tokenisation, so the documented `"booking guard"` scored zero unless that exact phrase appeared. The resulting false miss was written to `usage.jsonl` and recycled by the annotate skill as a "pre-validated annotation candidate", so one wrong example was quietly poisoning the feedback loop.

### Removed

- The navigate skill's "Why the vault beats raw exploration" table — six of its seven rows restated content elsewhere in the same file, and its right-hand column argued for a rule the skill had already stated in bold two sections above. Its one fact that a model cannot derive, that a note runs 20–40 lines, moved into "Anatomy of a note".
- The worked example from both skills. Each re-ran its own file's recipes with names attached, pinned no format-sensitive output, and a single gold trajectory invites five-step investigations of two-step tasks. What stays, deliberately: the annotate workflow's numbered steps (every ordering constraint there is a real dependency or a sign-off gate, and descriptions cannot be written before generation creates the slot), both Hard rules blocks, and the navigate tool table, which loads on trigger when the real tool list is already in context.

### Compatibility

- The local index format is now version 2. Existing indexes read as `unknown` until the next `etruscan:generate`.
- A published `config/etruscan.php` keeps its explicit `env('ETRUSCAN_SCANNED_FOLDERS', ['app', 'src'])` default and so stays strict. To opt into auto-detection, change it to `env('ETRUSCAN_SCANNED_FOLDERS')`.
- Regenerate after upgrading to remove former import-only links and build the local evidence index. Existing vaults remain readable with freshness reported as unknown until generation.
- Projects listing absent scan roots should correct their configuration before enabling strict checks. Source evidence currently covers named classes only; Laravel dynamic wiring remains outside this increment.
- Nothing to migrate for annotations: `use WellDigit\Etruscan\Attributes\…` keeps working, and the two spellings can mix on one class. Run `php artisan boost:update` to pick up the revised guideline and skills.
- A global twin is not `instanceof` its namespaced sibling. Code reading these attributes at runtime should ask for `EtruscanAxis` with `ReflectionAttribute::IS_INSTANCEOF`, which covers both spellings and any custom axis.
- Rector's `importNames()` adds `use EtruscanNode;` back unless `importShortClasses(false)` is set; the annotation still resolves either way. Pint leaves the global form as written.

## [1.2.0] - 2026-08-25

### Added

- A trust-protocol footer on every `lookup-node` and `trace-node` answer, with the same caveat in the MCP server instructions: the description is testimony — authoritative for intent and rationale — while enforcement claims (validation, authorization, expiry) are verified in the source before being repeated. Born from a live A/B run where a map-reading agent repeated a note's overstated validation claim that traced back to a lying controller docblock; a rule read once at session start had faded by tool call 39, so the reminder now travels with every note served. The wording has one owner, `NoteTrustReminder::LINE`, so the two tools cannot drift into different trust stories. _Provenance note added in 1.3.0: the A/B was never recorded against a named model, and this release shipped the footer, both skill rewrites and the guideline sentence in one commit, so the footer's own contribution was never isolated from the standing rules that landed beside it._

### Changed

- The navigate skill's trust rule is now a claim-class split instead of a soft "verify at source": notes are the authority on intent, rationale, history, and open questions — knowledge that is not in the code and cannot be verified from it — while the source is the authority on anything checkable; an enforcement claim is never repeated from a note without reading the enforcing code (`rules()`, a policy, middleware, the pipeline stage), and when note and source disagree on a checkable fact, the source is right and the description has earned a correction. The annotate skill gains the write-side counterpart: enforcement claims in a description are sourced from the enforcing code — never from a docblock or comment, which can overstate what the code below it does — and attributed to the layer that actually performs the check. The core guideline carries the same caveat in one sentence.

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
