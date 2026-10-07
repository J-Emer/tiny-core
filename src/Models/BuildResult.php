<?php

namespace Jemer\Tiny\Models;

class BuildResult
{
    /**
     * @param string[] $files absolute paths of every file this result wrote
     */
    public function __construct(
        public readonly string $name,
        public readonly string $path = '',
        public readonly bool $ok = true,
        public readonly ?string $message = null,
        public readonly string $action = 'built',
        public readonly array $files = [],
    ) {}

    public static function Success(string $name, string $path = '', ?array $files = null) : self
    {
        return new self($name, $path, true, null, 'built', $files ?? ($path !== '' ? [$path] : []));
    }

    public static function Removed(string $name, string $path) : self
    {
        return new self($name, $path, true, null, 'removed');
    }

    public static function Failure(string $name, string $message, string $action = 'built') : self
    {
        return new self($name, '', false, $message, $action);
    }
}