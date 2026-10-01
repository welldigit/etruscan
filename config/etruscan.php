<?php

declare(strict_types=1);

return [

    /*
    | Folders scanned for attributed classes, relative to the application
    | root (absolute paths are also accepted). Left unset (null), Etruscan
    | scans whichever of `app/` and `src/` exist. An explicit list is strict:
    | every entry is expected to exist, and a missing one is reported and
    | marks the scan incomplete. Set it with a comma-separated
    | ETRUSCAN_SCANNED_FOLDERS env, e.g.
    | ETRUSCAN_SCANNED_FOLDERS=app,src,packages/acme/src.
    */

    'scanned_folders' => env('ETRUSCAN_SCANNED_FOLDERS'),

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
    | stays on this machine unless you deliberately commit it. At 5 MB the
    | log rotates to `usage.jsonl.1`; one previous generation is kept.
    */

    'usage_tracking' => env('ETRUSCAN_USAGE_TRACKING', true),

    /*
    | Characters per token, used only to turn the measured character counts in
    | `php artisan etruscan:usage` into the token figure people reason about.
    |
    | Four is the familiar rule of thumb for English prose, and map payloads
    | are not English prose: they are dense with kebab aliases, backslashed
    | FQCNs and file paths, all of which tokenize worse than the rule assumes.
    | So the default is optimistic for this content, and deliberately left
    | where you can correct it without a code change.
    |
    | To calibrate for your own map and model, take a real served payload out
    | of `{vault}/.reports/usage.jsonl`, count it for real, and divide its
    | character count by the result:
    |
    |   ant messages count-tokens --model claude-opus-5 \
    |     --message '{role: user, content: "@./payload.txt"}' \
    |     --transform input_tokens -r
    |
    | Token counts are model-specific, so re-run it when you change models.
    */

    'chars_per_token' => env('ETRUSCAN_CHARS_PER_TOKEN', 4),

];
