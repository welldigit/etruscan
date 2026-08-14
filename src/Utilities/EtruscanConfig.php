<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Utilities;

use WellDigit\Etruscan\Attributes\EtruscanNode;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;

/**
 * The typed reading of `config/etruscan.php`. Every key is read here and
 * nowhere else, so a default lives in exactly two places — the published config
 * file and this class — instead of being retyped at each call site, where one
 * stale literal is enough to send a command and a tool to different vaults.
 * Returning settled types also keeps `mixed` from leaking out of `config()`
 * into the rest of the package.
 */
#[EtruscanNode('etruscan-config')]
#[EtruscanLayer('utility')]
#[EtruscanContext('cli')]
#[EtruscanContext('mcp')]
#[EtruscanContext('usage')]
final class EtruscanConfig
{
    /**
     * Absolute path to the vault, resolved against the app base path when the
     * configured value is relative.
     */
    public static function vaultPath(?string $override = null): string
    {
        if ($override !== null && $override !== '') {
            return AbsolutePathResolver::resolve($override);
        }

        return AbsolutePathResolver::resolve(self::string('vault_path', '.etruscan'));
    }

    /**
     * The frontmatter key marking a note as generated — the flag that tells the
     * writer what it may rewrite and the readers what belongs to the map.
     */
    public static function markerKey(): string
    {
        return self::string('generated_marker', 'generated_by');
    }

    public static function markerValue(): string
    {
        return self::string('generated_value', 'etruscan');
    }

    /**
     * @return list<string>
     */
    public static function scannedFolders(): array
    {
        return ScannedFolderResolver::resolve(config('etruscan.scanned_folders', ['app', 'src']));
    }

    public static function groupBy(): ?string
    {
        $groupBy = config('etruscan.group_by');

        if (is_array($groupBy)) {
            return implode(',', array_filter($groupBy, is_string(...)));
        }

        return is_string($groupBy) ? $groupBy : null;
    }

    /**
     * @return array<string, list<string>>
     */
    public static function vocabulary(): array
    {
        /** @var array<string, list<string>> $vocabulary */
        $vocabulary = (array) config('etruscan.vocabulary', []);

        return $vocabulary;
    }

    /**
     * Defaults to on, and a null stays on: an unset key and an explicitly-null
     * one are the same statement of "not configured", and silently switching
     * measurement off is the wrong reading of silence.
     */
    public static function usageTracking(): bool
    {
        $usageTracking = config('etruscan.usage_tracking', true);

        return $usageTracking === null || (bool) $usageTracking;
    }

    private static function string(string $key, string $default): string
    {
        $value = config('etruscan.'.$key, $default);

        return is_string($value) && $value !== '' ? $value : $default;
    }
}
