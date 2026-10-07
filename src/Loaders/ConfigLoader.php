<?php

namespace Jemer\Tiny\Loaders;

use Symfony\Component\Yaml\Yaml;
use RuntimeException;

class ConfigLoader
{
    private static array $config = [];
    private static array $defaults = [];
    private static array $overrides = [];

    /** Forget everything, so a new Kernel starts clean. */
    public static function Reset(): void
    {
        self::$config = [];
        self::$defaults = [];
        self::$overrides = [];
    }

    /** The core's built-in values; they sit underneath the project's config.yaml. */
    public static function SetDefaults(string $filePath): void
    {
        if (!is_file($filePath))
        {
            throw new RuntimeException("Default config not found: {$filePath}");
        }

        self::$defaults = Yaml::parseFile($filePath) ?? [];
    }

    /** Layers, lowest first: core defaults, then config.yaml, then overrides. */
    public static function Load(string $filePath): void
    {
        if (!is_file($filePath))
        {
            throw new RuntimeException("Config file not found: {$filePath} (is this the root of a Tiny project?)");
        }

        $parsed = Yaml::parseFile($filePath) ?? [];

        self::$config = array_replace_recursive(self::$defaults, $parsed, self::$overrides);
    }

    /**
     * Forces a value regardless of config.yaml, and keeps forcing it when the
     * file is loaded again (serve and build:prod use this for the base URL).
     */
    public static function Override(string $key, mixed $value): void
    {
        $nested = $value;

        foreach (array_reverse(explode('.', $key)) as $part)
        {
            $nested = [$part => $nested];
        }

        self::$overrides = array_replace_recursive(self::$overrides, $nested);
        self::$config = array_replace_recursive(self::$config, $nested);
    }

    public static function Get(string $key, mixed $default = null): mixed
    {
        $parts = explode('.', $key);
        $value = self::$config;

        foreach ($parts as $part)
        {
            if (!is_array($value) || !array_key_exists($part, $value))
            {
                return $default;
            }

            $value = $value[$part];
        }

        return $value;
    }
}
