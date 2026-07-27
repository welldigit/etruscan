## Etruscan (welldigit/etruscan)

This project keeps a generated map of its codebase in a markdown vault (`.etruscan/` by default, see `config/etruscan.php`) — one note per class annotated with `#[EtruscanNode]`, carrying its identity, description, and `## References` / `## Referenced by` links.

- **Understand the code through the map first.** To find a class, trace a dependency, or orient in an unfamiliar area, consult the map before crawling files — then open the real file via the note's `source` path. When the `etruscan` MCP server is connected, prefer its tools over file reads: `map-overview` (orient), `search-map` (find), `lookup-node` (read one node), `trace-node` (dependencies). The `etruscan-navigate` skill has the full workflow.
- **Keep the map fresh.** After you add, remove, or re-wire annotated classes, regenerate with `php artisan etruscan:generate`, and run `php artisan etruscan:check` to catch duplicate aliases, off-vocabulary axis values, orphans, and broken links.
- **Never hand-edit generated blocks** (frontmatter, `## References`, `## Referenced by`, and `## Description` when the class has a docblock) — they are overwritten on regeneration. Write human notes below them; those always survive.
- **Put new classes on the map** with `#[EtruscanNode('kebab-alias')]` plus axis attributes from `WellDigit\Etruscan\Attributes`. To annotate a whole codebase at once, use the `etruscan-annotate` skill.
