## Etruscan (welldigit/etruscan)

- This project maintains a generated markdown vault (default `vault/`, see `config/etruscan.php`) mapping annotated classes: one note per class with `alias`, `fqcn`, and `source` (file path) frontmatter, a `## Description` from the class docblock, and `## References` wikilinks to referenced classes.
- Use the vault for architectural orientation and dependency tracing, then open the real file via the note's `source` path. Load the `etruscan` skill for the full workflow.
- Regenerate with `php artisan etruscan:generate`; regeneration only rewrites generated blocks — human notes below them always survive. Never hand-edit generated blocks; write below them instead.
- Annotate new classes with `#[EtruscanNode('kebab-alias')]` and axis attributes from `WellDigit\Etruscan\Attributes` so they appear on the map.
