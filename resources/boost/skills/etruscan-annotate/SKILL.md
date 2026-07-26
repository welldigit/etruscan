---
name: etruscan-annotate
description: >
  Design and apply an Etruscan annotation schema for a codebase that has no
  map yet — or extend an existing one. This is the BOOTSTRAP skill: it decides
  which classes become nodes, what taxonomy (layer/domain/context/slice or
  custom axes) describes them, assigns stable aliases, and writes the
  `#[EtruscanNode]` + axis attributes into the source — so the human doesn't
  annotate hundreds of classes by hand. Trigger on: "annotate the codebase",
  "set up etruscan", "map this project", "add EtruscanNode attributes",
  "propose the taxonomy / axes", "which classes should be nodes", "fill the
  classes with attributes", "bootstrap the vault", or any request to design or
  apply the annotation scheme rather than read an existing vault. The sibling
  `etruscan-navigate` skill READS the vault this one CREATES.
---

# Etruscan — annotate the codebase (design the map)

`welldigit/etruscan` projects annotated classes into a markdown vault. Before
there is a vault, someone has to decide **what goes on the map and how it is
labelled**. That is this skill. You do the tedious, judgement-heavy part —
taxonomy, curation, aliases — and apply it in bulk, so a human reviews a plan
instead of editing every file.

## What you are producing

Two decisions, then the mechanical apply:

1. **The schema** — which axes describe this codebase and what values they take
   (`#[EtruscanLayer]`, `#[EtruscanDomain]`, `#[EtruscanContext]`,
   `#[EtruscanSlice]`, or a custom `EtruscanAxis` subclass).
2. **The nodes** — which classes are worth a note (`#[EtruscanNode('alias')]`),
   each with a permanent alias.

Then: write the attributes, run `php artisan etruscan:generate`, fix anything
it rejects.

## Before you start

- Read `config('etruscan.scanned_folders')` — only classes under those folders
  will ever be scanned. Annotating elsewhere is wasted work.
- Grep for existing `#[EtruscanNode(` — **never re-annotate or re-alias a class
  that already has one**; aliases are permanent (see Hard rules).
- Skim the sibling `etruscan-navigate` skill's "Anatomy of a note" so you design toward
  what the vault will actually render.

## Workflow

1. **Read the structure.** Walk the scanned folders. Most codebases already
   encode the taxonomy in their paths — `Core/Domain/Booking/Actions/BookingCreate.php`
   is `domain: booking`, `layer: action`; `app/Http/Controllers/Auth/LoginController.php`
   is `layer: controller`, `context: auth`. Infer axes from folder segments
   before inventing anything.
2. **Draft the schema.** Propose the axis vocabulary and its candidate values
   (see *Deciding the axes*). Recommend a `group_by` for the config. Keep it to
   the four bundled axes unless a real recurring dimension justifies a custom one.
3. **Select the nodes.** Apply the curation principles (see *Deciding what is a
   node*). Produce a candidate list grouped by folder/type, with the excluded
   buckets named explicitly.
4. **Assign aliases.** Kebab-case, globally unique, permanent (see *Assigning
   aliases*).
5. **Present the plan and get sign-off.** Show a table — `class → alias →
   layer / domain / context` — plus the proposed `group_by` and any custom axis.
   **Do not mass-edit source before the human approves it.** Let them tune the
   taxonomy, drop nodes, or rename aliases while it is still cheap.
6. **Apply and verify.** Write the imports and attributes (see *Applying the
   attributes*). Then `php artisan etruscan:generate --dry-run`; resolve every
   fail-loud error; generate for real; render `etruscan:graph` and sanity-check
   the shape. The tool's exceptions are your linter — trust them.

## Deciding the axes (the schema)

Only `#[EtruscanNode]` is fixed. **Everything else is an open vocabulary you
define** — the four bundled axes below are a suggested starting point, not a
convention you must follow. An axis is any `EtruscanAxis` subclass, so you can
name a dimension anything (`EtruscanTier`, `EtruscanTeam`, `EtruscanRisk`, …)
with any values that fit the codebase in front of you. Treat the four as a
sensible default to keep, extend, rename, or replace outright.

Each axis answers one question. Keep them from smearing into each other:

| Axis | Attribute | Answers | Typical source |
|---|---|---|---|
| `layer` | `EtruscanLayer` | What *kind* of class, architecturally? | the type folder: `Actions`→`action`, `Models`→`model`, `Services`→`service` |
| `domain` | `EtruscanDomain` | Which *business area* owns it? | `Core/Domain/{X}` segment |
| `context` | `EtruscanContext` | Which *bounded context*? Repeatable. | usually = domain; add more only when a class genuinely serves several |
| `slice` | `EtruscanSlice` | Which *vertical feature*, across layers/domains? | cross-cutting flows: `Checkout`, `Onboarding` |

- Values are lowercase, singular, and consistent — `action` not `Actions`,
  `booking` not `Bookings`. The value is what appears in frontmatter and in
  folder grouping.
- **Invent your own axis** whenever a dimension matters to this codebase and the
  bundled four don't capture it — that is the intended path, not a workaround.
  Create it as `final readonly class EtruscanCriticality extends EtruscanAxis`
  with its own `#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]`
  marker (`#[EtruscanCriticality('high')]` → `criticality: high`). The key is its
  short name minus a leading `Etruscan`, lowercased — and must not be one of the
  identity keys `alias`, `class`, `fqcn`, `extends`, `source`. Keep each axis
  earning its place, though: another axis helps only if it slices the graph in a
  way someone will actually use.
- Recommend a `group_by`: a single axis (`domain`) for a flat business view, or
  nested (`layer,domain`) for a filing-cabinet layout. It is only presentation —
  wikilinks are path-independent — so optimise for how the human will browse.

## Deciding what is a node (curation)

**A map where everything is a node is a map of nothing.** The signal of the
vault is that a human deliberately marked each class as worth knowing.

Annotate the classes that carry meaning and participate in the graph — the
ones a new developer would want a signpost to: Actions, Models, Services,
Orchestrators, Jobs, Events, Listeners, Policies, Rules, Queries, invokable
Controllers, Data objects, behavioural Enums, Observers, Pipelines, Payloads.

Skip the glue and the noise: migrations, factories, seeders, config, service
providers, framework base classes, trivial value objects with no behaviour, and
one-off DTOs used only inside a single class. When unsure, **leave it off** —
adding a node later is one attribute; a bloated map is expensive to prune and
dulls the signal for every reader.

Heuristic: *if removing this class would puzzle a new developer, or other
meaningful classes point at it, it belongs on the map.*

## Assigning aliases

- Kebab-case of the class short name: `BookingCreate` → `booking-create`,
  `MonitorQuery` → `monitor-query`.
- **Drop the type suffix the `layer` axis already names** — the note carries
  `layer: exception`, so `AliasCollisionException` → `alias-collision`;
  `layer: command`, so `GenerateVaultCommand` → `generate-vault`. Cleaner, and
  the axis is not repeated in the name.
- **Globally unique.** Two `Status` enums in different domains become
  `booking-status` and `invoice-status` — prefix with the domain to break
  collisions. The tool throws `AliasCollisionException` if you miss one; that is
  your safety net, not a substitute for choosing well.
- Treat an alias like a public API name: it keys the note file, every wikilink,
  and the human's manual notes forever. Renaming it later orphans all of those.

## Applying the attributes

Per node class, add the imports and the attributes directly above the class,
**after** the docblock (Pint keeps that order):

```php
use WellDigit\Etruscan\Attributes\EtruscanNode;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;

/**
 * Creates a booking after guarding availability.
 */
#[EtruscanNode('booking-create')]
#[EtruscanLayer('action')]
#[EtruscanContext('booking')]
final readonly class BookingCreate { /* … */ }
```

- Repeat an axis for multiple values: `#[EtruscanContext('booking')]`
  `#[EtruscanContext('billing')]`.
- The **docblock summary becomes the note's `## Description`.** If a class has a
  crisp one-line summary, it flows into the vault for free. You *may* add a
  missing one-line summary — but summarise only what the code demonstrably does;
  never invent behaviour. Classes with no docblock get their description from
  human-written notes in the vault instead (see the `etruscan-navigate` skill).
- Work in reviewable batches (by folder or domain), not one 300-file sweep.

## The description is a specification, not a label

The `## Description` section is the highest-value part of a note — it is where
a class's **specification in plain language** lives: not just *what* it is, but
*why* it exists, *when* it runs, *how* it behaves, and *what business logic and
invariants* it holds. A reader should come away understanding the class's role
without opening the source — the rules it enforces, the decisions it makes, the
edge cases it guards, the reason it was built this way.

Two places carry this, and they are owned differently:

- **The docblock summary** (auto-mirrored into `## Description`, regenerated
  with the code) stays a crisp, accurate one-liner. Keep it factual —
  summarise only what the code demonstrably does; never speculate here, because
  it must never drift from the source it mirrors.
- **The human specification** — the fuller *why / when / how / business-rule*
  narrative — belongs in the vault. When a class has no docblock, the
  `## Description` section is human-owned and survives every regeneration, so
  write the real specification straight into it. When a class *does* have a
  docblock, put the deeper narrative in the manual notes below the generated
  blocks. Either way this is knowledge **not recoverable from reading the
  code** — the intent, the constraints, the "we chose this because…" — and it
  is exactly what makes the map worth more than a class list.

As the annotating agent, **draft a first-pass specification** for each node
where you can state it with confidence from the code and its context — a real
paragraph a developer would recognise, not the class name restated. Because this
text is human-owned and **never regenerated**, it becomes a living document the
developer **enriches over time** with the business rules, constraints, and
decisions only they hold. Flag the classes whose *why* you cannot source from
the code — those are prompts for a human to fill in, not gaps to invent over.

## Generate and fix

- `php artisan etruscan:generate --dry-run` — see the counts and folder shape
  before writing anything.
- Resolve the fail-loud guards, each of which points at a real schema mistake:
  - `AliasCollisionException` → two classes share an alias; disambiguate one.
  - `ReservedAxisKeyException` → a custom axis key shadows an identity key;
    rename the axis class.
  - `InvalidGroupingValueException` → an axis value can't become a folder name;
    fix the value.
- Then generate for real, and `php artisan etruscan:graph` to eyeball clusters
  and orphans — an isolated node often means a missed reference or a mis-scoped
  domain.

## Hard rules

1. **Propose before you apply.** Never silently rewrite dozens of files — a
   plan the human approves is the whole point of the skill.
2. **Curate.** Start conservative; the value of the map is what you left off.
3. **Aliases are permanent.** Choose them like public API; never rename an
   existing one on your own initiative.
4. **One responsibility per axis.** `layer` = what kind, `domain` = whose,
   `context` = which boundary, `slice` = which feature. Don't blur them.
5. **Never collide with identity keys** (`alias`, `class`, `fqcn`, `extends`,
   `source`) when naming a custom axis.
6. **Never touch existing annotations or manual notes.** Extend the map; don't
   rewrite what is already on it.
7. **Docblocks state only what the code does; specifications explain why.** A
   docblock summary you add (auto-mirrored, regenerated) must not carry claims
   the source doesn't back. The richer *why / when / business-logic*
   specification belongs in the human-owned Description or manual notes — write
   what you can support from the code and context, and flag the rest for the
   human rather than inventing intent.

## Worked example

Human: *"Set up etruscan for this app — it's Labrodev-style under `src/Core`."*

1. **Read.** `scanned_folders` = `['src']`. Folders reveal the taxonomy:
   `src/Core/Domain/{Booking,Invoice,Monitor}/{Actions,Models,Queries,Policies,…}`.
2. **Schema.** `layer` from the type folder; `domain` from the `Domain/{X}`
   segment; `context` = domain by default. No custom axis needed. Recommend
   `group_by: domain`.
3. **Nodes.** ~90 candidates: Actions, Models, Services, Queries, Policies,
   Rules, Jobs, Events, Data, behavioural Enums. Excluded (named for the human):
   migrations, factories, the abstract `BaseModel`, the base `Controller`,
   value objects with no behaviour.
4. **Aliases.** `BookingCreate`→`booking-create`, `Booking`→`booking`,
   `BookingOverlapException`→`booking-overlap` (dropped `Exception`; `layer`
   says it), `BookingStatus`→`booking-status` (domain-prefixed vs `InvoiceStatus`).
5. **Plan.** A table of all ~90 → alias / layer / domain, plus
   `group_by: domain`. Human trims a few DTOs, approves.
6. **Apply & verify.** Attributes written per domain; `--dry-run` shows 88
   nodes, 3 folders; one `AliasCollisionException` (`Status` in two domains) →
   domain-prefix both; regenerate clean; graph shows three tidy domain clusters.

Now the sibling `etruscan-navigate` skill can read the map you just built.
