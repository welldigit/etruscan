---
name: etruscan-navigate
description: >
  The codebase-navigation skill for this project. Use it BEFORE any codebase
  exploration — before walking the file tree, before broad grep, before opening
  files "to look around" — so that when you do open a file, you already know
  it is the right one. The Etruscan vault (default `.etruscan/` at app root)
  is a generated markdown map of the codebase: one markdown note per meaningful
  class, with description, taxonomy, source path, and precomputed dependency
  links. Use it whenever a task needs an understanding of code that already
  exists: orienting in an unfamiliar area, locating the class that owns a
  concept, tracing what a class uses or what uses it, or planning a change to
  an existing class — any task where you would otherwise read source files to
  build context.
---

# Etruscan — let the map tell you which code to read

This project ships a generated markdown vault (`welldigit/etruscan`): one note
per annotated class. It is not documentation *about* the code: the derived
sections — identity, references, inverse references — are an index *generated
from* the code. They can become stale; tool responses report freshness and
`etruscan:check --fresh --strict` compares them with current source.
The `## Description` is human testimony kept beside those derived edges.

## The one rule

**When you need to understand this codebase — orientation, structure, a class's
role, a dependency trace — open the vault first, then go to the source it points
at, through the note's `source` path.**

The vault is an index of the code, not a replacement for reading it. What it
replaces is the guessing: instead of a file-tree crawl and five speculative
reads, you open the two files that matter. You still read them.

**Prefer the Etruscan MCP tools when the `etruscan` server is connected** —
one call narrows a whole codebase to the handful of files worth opening, and every consultation
(including what you looked for and could NOT find) is measured locally so the
team can improve the map:

| Tool | Use it for |
|---|---|
| `map-overview` | orient: every node grouped by a taxonomy axis, in one call |
| `search-map` | find nodes by name or concept (aliases, classes, descriptions) |
| `lookup-node` | read one node in full, then open its `source` path |
| `trace-node` | annotated references plus paginated source evidence, including unannotated callers |

No MCP connection? Read the `.etruscan/` files directly — everything below
works either way.

## Anatomy of a note

```markdown
---
alias: monitor-create              # permanent name; filename stem; [[wikilink]] target
class: MonitorCreate               # short class name
fqcn: Core\Domain\Monitor\Actions\MonitorCreate
extends: Core\Shared\BaseAction    # present only when the class extends something
source: src/Core/Domain/Monitor/Actions/MonitorCreate.php   # app-relative path — open it directly
layer: action                      # open-vocabulary axes: layer, domain, context, slice, custom…
context: monitor
generated_by: etruscan             # machine-managed marker
---

## Description

Creates a monitor for the account after guarding the plan cap.

## References

- [[monitor]]
- [[monitor-create-data]]

## Referenced by

- [[monitor-store-controller]]

Anything below the generated blocks is a human's manual notes. Protected.
```

`## References` is what the class points at (outbound); `## Referenced by` is
who points at it (inbound) — so both directions of a dependency are on the note.

**`## Description` is a specification, not a caption — and it is manual.** The
generator seeds the empty heading in every note; that slot is its only
contribution. The text is **human-owned and never regenerated**: written
straight into the note (by a developer, or by an agent writing as one), keyed
to the alias, and carried untouched through every regeneration, taxonomy
change, and folder move. It is a
real plain-language spec of *why the class exists, when it runs, and what
business rules it holds* — never harvested from docblocks or source. Treat it as
authoritative human knowledge you cannot recover from the code, and read it
before you judge a class from its name. The axes (`layer`, `domain`, `context`,
`slice`, or any custom one) are an open vocabulary — a project defines whatever
dimensions fit it, so don't assume the four bundled ones are all you'll see.

A note runs 20–40 lines, so a subsystem's worth of them costs less context than
one controller — and unlike a file tree, everything in the vault is there
because a human marked it worth knowing.

Vault location: `config('etruscan.vault_path')`, default `.etruscan/`
(`ETRUSCAN_VAULT` env overrides). Notes may be flat or grouped into folders by
axis (`config('etruscan.group_by')`); wikilinks resolve identically either way.

## Recipes

**Find a class / understand what it does**

1. Locate the note: alias is the filename — `.etruscan/**/monitor-create.md`.
2. Read `## Description` for intent (human-written — an empty one is an
   unfilled slot worth filling, not a generator fault), frontmatter for role
   (`layer`, `context`…), and any manual notes below `## References`.
3. Need implementation detail? Open the file at `source`. The note is a
   signpost, never a substitute — and on anything checkable (what is
   validated, authorized, guarded), the code is the authority, not the prose.

**Map the whole codebase / a subsystem**

- List note filenames — that alone is the inventory of what matters.
- Slice by any axis: `grep -rl 'layer: action' .etruscan/` or
  `grep -rl 'context: monitor' .etruscan/`.
- Read the descriptions of one slice (cheap) before opening any source file.

**Trace outgoing dependencies (what does X use?)**

- The note's `## References` list. Each `[[alias]]` is an annotated class it
  references. Follow the chain note-to-note; only drop into source where needed.

**Trace inbound dependencies (who uses X?)**

- Read the note's `## Referenced by` list — the inbound edges are precomputed
  there. (Equivalently, `grep -rl '\[\[monitor-create\]\]' .etruscan/`.)
- **Run this before modifying any annotated class.** The note covers annotated
  neighbours only. Use `trace-node` for source evidence from unannotated callers;
  follow `evidence_offset` until you have inspected the relevant pages. Static
  references are not a complete call graph: check dynamic Laravel wiring in source.

**Plan a change to class X**

1. Read X's note: description, axes, references, and any manual notes below
   the generated blocks — the human may have recorded constraints there.
2. Grep inbound links (above) to see what the change touches.
3. Open the referenced source locations and verify behavior. Expand the search
   when bindings, listeners, anonymous classes, or unscanned code may participate.

**Talk to the human about code**

- Use aliases. `monitor-create` is a shared, stable name — the human browses
  the same vault. Prefer "this touches `monitor-create` and
  `monitor`" over paths and FQCNs.

**Vault missing or stale**

- Generate it, don't report it absent: `php artisan etruscan:generate`
  (`--dry-run` to preview). Regeneration is safe — only generated blocks are
  rewritten; human content survives and travels with relocated notes.
- After changing PHP classes in the configured scan roots, including unannotated callers:
  regenerate (or tell the human to). Regeneration never touches descriptions —
  they are the human's.
- Suspect the map is inconsistent? `php artisan etruscan:check` reports
  source-to-note drift, scan diagnostics, stale exported content, duplicate aliases,
  vocabulary issues, and broken links. Use `--fresh --strict` for a read-only CI check.

**Freshness and search misses**

- `current` means scanned contents match generation, not that human claims or
  dynamic behavior have been verified. `stale`, `incomplete`, and `unknown` need
  regeneration or scan-diagnostic resolution before relying on source evidence.
  `unknown` on a fresh checkout just means the local index was never built:
  `php artisan etruscan:generate` rebuilds it and leaves current notes unchanged.
- Without MCP, run `etruscan:check --fresh --strict` before using note links.
- A miss can mean different terminology or a search limitation. Every word of a
  query must match somewhere on a note, so drop words before concluding a miss;
  try another term and inspect source before deciding a class needs an annotation.

**A meaningful class has no note**

- The map is curated: on the map ⇒ it matters; matters but missing ⇒ annotate.
- Add `#[\EtruscanNode('kebab-alias')]` plus axes (`#[\EtruscanLayer('action')]`,
  `#[\EtruscanContext('monitor')]`, …) — global classes, no import, and the leading
  backslash is required — then regenerate, and fill the empty `## Description` seeded
  in the new note, as a human would.

## Hard rules

1. **Never edit the frontmatter, `## References`, or `## Referenced by`** —
   the generator owns them; your edits will be overwritten and may corrupt the
   map. `## Description` is the opposite: it is human-owned, never regenerated,
   and carried with the alias — edit it only to genuinely improve the human
   record, never to mechanically "sync" it with code. Correcting a description
   your own change has just invalidated IS improving the record — do it, and
   tell the human what you corrected.
2. **Never delete or rewrite human content** — anything below the generated
   blocks, and any note *without* the `generated_by` marker (those are entirely
   hand-written documentation). This content carries decisions you cannot
   recover from code. Read it; leave it.
3. **The alias is permanent.** Never rename an alias on your own initiative —
   it anchors every wikilink and the human's notes.
4. **Axes are lenses, not truth.** `layer`/`domain`/`context`/`slice` (and any
   `EtruscanAxis` subclass) describe how the graph is sliced; the reference edges
   come from real code references. Don't infer runtime behavior from taxonomy alone.
5. **Notes and source are authoritative for different claims.** A description
   is testimony — authoritative for intent, rationale, history, and open
   questions: knowledge that is not in the code and cannot be verified from it.
   For enforcement claims — validation, authorization, ownership, expiry,
   uniqueness, state transitions — the source is the authority: never repeat
   one from a note without reading the code that enforces it (`rules()`, a
   policy, middleware, the pipeline stage). A description can inherit an
   overstated docblock or lag a refactor; the enforcing code cannot. When note
   and source disagree on a checkable fact, the source is right — and the
   description has earned a correction (rule 1).
