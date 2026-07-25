<?php

declare(strict_types=1);

return [

    /*
    | Directories scanned for attributed classes. Missing directories are
    | skipped, so listing roots a project may not have is harmless. The
    | ETRUSCAN_ROOTS env (comma-separated paths) overrides the default.
    */

    'roots' => env('ETRUSCAN_ROOTS') !== null
        ? array_map(trim(...), explode(',', (string) env('ETRUSCAN_ROOTS')))
        : [
            base_path('app'),
            base_path('src'),
        ],

    /*
    | Where the notes go. Generated notes are rewritten and stale ones removed
    | on every run; manual content inside them always survives.
    */

    'vault_path' => env('ETRUSCAN_VAULT', base_path('vault')),

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

];
