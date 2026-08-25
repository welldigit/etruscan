---
name: etruscan-navigate
description: >
  The codebase-navigation skill for this project. Use it BEFORE any codebase
  exploration — before walking the file tree, before broad grep, before opening
  files "to look around". The Etruscan vault (default `.etruscan/` at app root)
  is a generated markdown map of the codebase: one markdown note per meaningful
  class, with description, taxonomy, source path, and precomputed dependency
  links. Trigger on: "how is this codebase structured", "where is X", "what
  does X do", "what depends on X", "trace this flow", "find the class that…",
  planning a change to an existing class, onboarding into an unfamiliar area,
  or any task where you would otherwise explore source files to build context.
---

# Etruscan — read the map before you crawl the code

This project ships a generated markdown vault (`welldigit/etruscan`): one note
per annotated class. It is not documentation *about* the code: the derived
sections — identity, references, inverse references — are an index *generated
from* the code, so they cannot drift, and the `## Description` is human
testimony kept right beside those freshly derived edges.

## The one rule

**When you need to understand this codebase — orientation, structure, a class's
role, a dependency trace — open the vault first. Go to source files second,
through the note's `source` path.**

Cold file-tree crawling is the fallback, not the default. The vault exists so
you never have to guess which files matter.

**Prefer the Etruscan MCP tools when the `etruscan` server is connected** —
one call replaces a glob-plus-read round trip, and every consultation
(including what you looked for and could NOT find) is measured locally so the
team can improve the map:

| Tool | Use it for |
|---|---|
| `map-overview` | orient: every node grouped by a taxonomy axis, in one call |
| `search-map` | find nodes by name or concept (aliases, classes, descriptions) |
| `lookup-node` | read one node in full, then open its `source` path |
| `trace-node` | follow dependency edges out (uses), in (used by), or both |

No MCP connection? Read the `.etruscan/` files directly — everything below
works either way.

## Why the vault beats raw exploration — concretely

| You get | From the vault | From raw exploration |
|---|---|---|
| Signal | Only classes a human marked `#[EtruscanNode]` — every note matters | Framework noise, vendor code, boilerplate |
| Cost | A note is ~20–40 lines; 10 notes ≈ one subsystem overview | One controller can cost more context than 10 notes |
| Dependencies | `## References` — precomputed edges from real code references | You reverse-engineer imports file by file |
| Location | `source` (exact file path) + `fqcn` (exact symbol) | Search and hope |
| Intent | `## Description` — the human's own words, kept on the note | Read the whole class to infer purpose |
| Human knowledge | Manual notes below the generated blocks — decisions and context **not recoverable from code** | Doesn't exist anywhere else |
| Shared vocabulary | Aliases are the names the human uses too — `monitor-create` means the same thing to both of you | You and the human describe code differently |

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
- **Run this before modifying any annotated class.** It is the blast radius.

**Plan a change to class X**

1. Read X's note: description, axes, references, and any manual notes below
   the generated blocks — the human may have recorded constraints there.
2. Grep inbound links (above) to see what the change touches.
3. Only then open source files — you now know exactly which ones.

**Talk to the human about code**

- Use aliases. `monitor-create` is a shared, stable name — the human browses
  the same vault. Prefer "this touches `monitor-create` and
  `monitor`" over paths and FQCNs.

**Vault missing or stale**

- Generate it, don't report it absent: `php artisan etruscan:generate`
  (`--dry-run` to preview). Regeneration is safe — only generated blocks are
  rewritten; human content survives and travels with relocated notes.
- After you change annotated classes, their attributes, or their imports:
  regenerate (or tell the human to). Regeneration never touches descriptions —
  they are the human's.
- Suspect the map is inconsistent? `php artisan etruscan:check` reports
  duplicate aliases, off-vocabulary axis values, and broken
  `[[wikilinks]]` — run it rather than eyeballing.

**A meaningful class has no note**

- The map is curated: on the map ⇒ it matters; matters but missing ⇒ annotate.
- Add `#[EtruscanNode('kebab-alias')]` plus axes (`#[EtruscanLayer('action')]`,
  `#[EtruscanContext('monitor')]`, …) from `WellDigit\Etruscan\Attributes`, then
  regenerate — and fill the empty `## Description` seeded in the new note, as a
  human would.

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

## Worked example

Human: *"How does monitor creation work, and is it safe to add a quota check?"*

1. `.etruscan/**/monitor-create.md` → Description: "Creates a monitor for the
   account after guarding the plan cap." Layer `action`, context `monitor`.
   A manual note below warns: "plan-cap guard must run before persistence —
   billing depends on it."
2. References: `[[monitor]]`, `[[monitor-create-data]]` → skim both notes;
   `monitor-create-data` is the input DTO.
3. Inbound: `grep -rl '\[\[monitor-create\]\]' .etruscan/` → two callers.
4. Open `src/Core/Domain/Monitor/Actions/MonitorCreate.php` via `source`.
5. Answer using aliases, respecting the human note's constraint on guard order.

Total cost: three short notes and one source file — instead of a directory walk
and five speculative file reads. That is the point of the vault.
