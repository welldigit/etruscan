<?php

declare(strict_types=1);

return [

    /*
    | Folders scanned for attributed classes, relative to the application
    | root (absolute paths are also accepted). Missing folders are skipped,
    | so listing ones a project may not have is harmless. Override with a
    | comma-separated ETRUSCAN_SCANNED_FOLDERS env, e.g.
    | ETRUSCAN_SCANNED_FOLDERS=app,src,packages/acme/src.
    */

    'scanned_folders' => env('ETRUSCAN_SCANNED_FOLDERS', ['app', 'src']),

    /*
    | Where the notes go, relative to the application root (an absolute path
    | is also accepted). Defaults to a hidden `.etruscan` directory so the map
    | sits beside the code without cluttering the project root. Generated notes
    | are rewritten and stale ones removed on every run; manual content inside
    | them always survives.
    */

    'vault_path' => env('ETRUSCAN_VAULT', '.etruscan'),

    /*
    | Axis key(s) used to group notes into subfolders. An axis key is the
    | frontmatter name derived from an axis attribute (EtruscanLayer => layer);
    | bundled keys: layer, domain, context, slice. One key gives
    | {vault}/{value}/{alias}.md; a comma-separated list (or array) nests
    | folders in that order, e.g. 'layer,domain' =>
    | {vault}/{layer}/{domain}/{alias}.md. A note missing an axis skips
    | that level. null keeps the vault flat.
    | Override per-run with --group-by ("none" forces flat).
    */

    'group_by' => env('ETRUSCAN_GROUP_BY'),

    /*
    | Frontmatter key/value stamped on generated notes. Only files carrying
    | the key are ever overwritten or removed — hand-written notes are safe.
    */

    'generated_marker' => 'generated_by',

    'generated_value' => 'etruscan',

    /*
    | Optional allowed values per axis, enforced by `php artisan etruscan:check`.
    | List an axis here to lock it to a known set — off-vocabulary values become
    | errors, which stops a typo like 'serivce' from silently fragmenting the
    | taxonomy. Axes left out stay free-form and get fuzzy typo warnings instead.
    |
    |   'vocabulary' => [
    |       'layer'  => ['action', 'model', 'service', 'query', 'data', 'observer', 'policy'],
    |       'domain' => ['booking', 'invoice', 'monitor'],
    |   ],
    */
    'vocabulary' => [],

    /*
    | Local usage measurement. When enabled (the default), the Etruscan MCP
    | tools append every map consultation — including the misses that tell
    | you what to annotate next — to `{vault}/.reports/usage.jsonl`, and
    | `php artisan etruscan:usage` aggregates the log into a report.
    | The `.reports` folder is seeded with its own `.gitignore`, so the log
    | stays on this machine unless you deliberately commit it.
    */

    'usage_tracking' => env('ETRUSCAN_USAGE_TRACKING', true),

];
