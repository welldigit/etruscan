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
per annotated class. It is not documentation *about* the code — it is an index
*generated from* the code, so it cannot drift.

## The one rule

**When you need to understand this codebase — orientation, structure, a class's
role, a dependency trace — open the vault first. Go to source files second,
through the note's `source` path.**

Cold file-tree crawling is the fallback, not the default. The vault exists so
you never have to guess which files matter.

## Why the vault beats raw exploration — concretely

| You get | From the vault | From raw exploration |
|---|---|---|
| Signal | Only classes a human marked `#[EtruscanNode]` — every note matters | Framework noise, vendor code, boilerplate |
| Cost | A note is ~20–40 lines; 10 notes ≈ one subsystem overview | One controller can cost more context than 10 notes |
| Dependencies | `## References` — precomputed edges from real code references | You reverse-engineer imports file by file |
| Location | `source` (exact file path) + `fqcn` (exact symbol) | Search and hope |
| Intent | `## Description` — mirrors the docblock, or human-maintained when there is none | Read the whole class to infer purpose |
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

**`## Description` is a specification, not a caption.** When a class has a
docblock it mirrors that (and regenerates with the code). When it does not, the
section is **human-owned and never regenerated** — a real plain-language spec of
*why the class exists, when it runs, and what business rules it holds*, drafted
by an agent and enriched by developers over time. Treat it as authoritative
human knowledge you cannot recover from the code, and read it before you judge a
class from its name. The axes (`layer`, `domain`, `context`, `slice`, or any
custom one) are an open vocabulary — a project defines whatever dimensions fit
it, so don't assume the four bundled ones are all you'll see.

Vault location: `config('etruscan.vault_path')`, default `.etruscan/`
(`ETRUSCAN_VAULT` env overrides). Notes may be flat or grouped into folders by
axis (`config('etruscan.group_by')`); wikilinks resolve identically either way.

## Recipes

**Find a class / understand what it does**

1. Locate the note: alias is the filename — `.etruscan/**/monitor-create.md`.
2. Read `## Description` for intent (docblock-mirrored or human-written — it
   is always there), frontmatter for role (`layer`, `context`…), and any
   manual notes below `## References`.
3. Need implementation detail? Open the file at `source`. The note is a
   signpost, never a substitute.

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
- After you change annotated classes, their imports, or docblocks: regenerate
  (or tell the human to).
- Suspect the map is inconsistent? `php artisan etruscan:check` reports
  duplicate aliases, off-vocabulary axis values, orphan nodes, and broken
  `[[wikilinks]]` — run it rather than eyeballing.

**A meaningful class has no note**

- The map is curated: on the map ⇒ it matters; matters but missing ⇒ annotate.
- Add `#[EtruscanNode('kebab-alias')]` plus axes (`#[EtruscanLayer('action')]`,
  `#[EtruscanContext('monitor')]`, …) from `WellDigit\Etruscan\Attributes`, and a
  docblock summary — it becomes the note's Description. Then regenerate.

## Hard rules

1. **Never edit the frontmatter or `## References`** — the generator owns
   them; your edits will be overwritten and may corrupt the map.
   `## Description` is owned by the docblock when the class has one (edits
   are overwritten); when the class has no docblock, the Description section
   is human-owned and survives regeneration.
2. **Never delete or rewrite human content** — anything below the generated
   blocks, and any note *without* the `generated_by` marker (those are entirely
   hand-written documentation). This content carries decisions you cannot
   recover from code. Read it; leave it.
3. **The alias is permanent.** Never rename an alias on your own initiative —
   it anchors every wikilink and the human's notes.
4. **Axes are lenses, not truth.** `layer`/`domain`/`context`/`slice` (and any
   `EtruscanAxis` subclass) describe how the graph is sliced; the reference edges
   come from real code references. Don't infer runtime behavior from taxonomy alone.
5. **Descriptions are trustworthy; still verify at source** before making
   claims about implementation details.

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
