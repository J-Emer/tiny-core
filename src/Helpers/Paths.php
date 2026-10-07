<?php

namespace Jemer\Tiny\Helpers;

use Jemer\Tiny\Loaders\ConfigLoader;
use RuntimeException;

class Paths
{
    private static ?string $root = null;

    /** Sets the user's project root. Call once, before anything else. */
    public static function SetRoot(string $root) : void
    {
        self::$root = rtrim($root, '/\\');
    }

    public static function Root() : string
    {
        return self::$root
            ?? throw new RuntimeException('Project root not set. Call Paths::SetRoot() first.');
    }

    public static function ConfigFile() : string
    {
        return PathHelper::BuildPath([self::Root(), 'config.yaml']);
    }

    /**
     * Absolute path for a key under "paths." in config.yaml.
     * Paths::Get('output') => {root}/public
     */
    public static function Get(string $key, string ...$extra) : string
    {
        $configured = ConfigLoader::Get('paths.' . $key)
            ?? throw new RuntimeException("Missing config value: paths.{$key}");

        return PathHelper::BuildPath([self::Root(), $configured, ...$extra]);
    }
}
