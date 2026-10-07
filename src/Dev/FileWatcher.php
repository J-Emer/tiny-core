<?php

namespace Jemer\Tiny\Dev;

use Symfony\Component\Finder\Finder;

/**
 * Polling file watcher: PHP has no built-in file-system events, so we take a
 * snapshot of (modified time, size) for every watched file and compare.
 */
class FileWatcher
{
    /** @var callable(): string[] */
    private $targets;

    /**
     * @param callable(): string[] $targets returns the files/folders to watch.
     *        It is called on every snapshot, so config changes are picked up.
     */
    public function __construct(callable $targets)
    {
        $this->targets = $targets;
    }

    /** @return array<string, string> path => "mtime:size" */
    public function Snapshot() : array
    {
        clearstatcache(true);
        $files = [];

        foreach (($this->targets)() as $target)
        {
            if (is_file($target))
            {
                $this->Record($files, $target);
            }
            elseif (is_dir($target))
            {
                foreach ((new Finder())->files()->in($target)->ignoreUnreadableDirs() as $file)
                {
                    $this->Record($files, $file->getPathname());
                }
            }
        }

        return $files;
    }

    /**
     * @param  array<string, string> $before
     * @param  array<string, string> $after
     * @return array<string, string> path => 'added' | 'changed' | 'removed'
     */
    public static function Changes(array $before, array $after) : array
    {
        $changes = [];

        foreach ($after as $path => $signature)
        {
            if (!isset($before[$path]))
            {
                $changes[$path] = 'added';
            }
            elseif ($before[$path] !== $signature)
            {
                $changes[$path] = 'changed';
            }
        }

        foreach ($before as $path => $signature)
        {
            if (!isset($after[$path]))
            {
                $changes[$path] = 'removed';
            }
        }

        return $changes;
    }

    private function Record(array &$files, string $path) : void
    {
        $modified = @filemtime($path);
        $size = @filesize($path);

        // Editors create and delete temp files; a file that vanished mid-scan is skipped
        if ($modified !== false && $size !== false)
        {
            $files[$path] = "{$modified}:{$size}";
        }
    }
}
