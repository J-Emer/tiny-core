<?php

namespace Jemer\Tiny\Generators;

use Jemer\Tiny\Helpers\PathHelper;
use Jemer\Tiny\Helpers\Paths;
use Jemer\Tiny\Models\BuildResult;
use Symfony\Component\Filesystem\Path;

/**
 * Remembers which files Tiny wrote into the output folder (in a manifest kept
 * in the project root) so stale ones can be removed later, without touching
 * files Tiny didn't create.
 */
class OutputCleaner
{
    private string $output;
    private string $manifestPath;

    public function __construct()
    {
        $this->output = Paths::Get('output');
        $this->manifestPath = Paths::Get('manifest'); // kept outside public/, so it is never deployed
    }

    /**
     * Call after a full build.
     *
     * $complete = true:  delete files the previous build made but this one didn't.
     * $complete = false: delete nothing and keep tracking everything (used when
     *                    the build had failures, or with --no-clean).
     *
     * @param  string[] $generated absolute paths written by this build
     * @return BuildResult[]
     */
    public function Sync(array $generated, bool $complete = true) : array
    {
        $current = $this->ToRelative($generated);
        $previous = $this->Load();

        if (!$complete)
        {
            $this->Save(array_merge($previous, $current));
            return [];
        }

        [$results, $kept] = $this->RemoveFiles(array_diff($previous, $current));
        $this->Save(array_merge($current, $kept));

        return $results;
    }

    /** Record files written outside a full build (build:single). */
    public function Track(array $generated) : void
    {
        $this->Save(array_merge($this->Load(), $this->ToRelative($generated)));
    }

    /**
     * Delete every file in the manifest.
     *
     * @return BuildResult[]
     */
    public function RemoveAll() : array
    {
        [$results, $kept] = $this->RemoveFiles($this->Load());

        if ($kept === [])
        {
            @unlink($this->manifestPath);
        }
        else
        {
            $this->Save($kept);
        }

        return $results;
    }

    /** @return string[] paths relative to the output folder */
    private function Load() : array
    {
        if (!is_file($this->manifestPath))
        {
            return [];
        }

        $data = json_decode((string) file_get_contents($this->manifestPath), true);
        $files = is_array($data) ? ($data['files'] ?? []) : [];

        return is_array($files) ? array_values(array_filter($files, 'is_string')) : [];
    }

    private function Save(array $relatives) : void
    {
        $relatives = array_values(array_unique($relatives));
        sort($relatives);

        file_put_contents(
            $this->manifestPath,
            json_encode(['files' => $relatives], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    private function ToRelative(array $absolutePaths) : array
    {
        return array_values(array_unique(array_map(
            fn(string $path) => Path::makeRelative($path, $this->output),
            $absolutePaths
        )));
    }

    /**
     * @return array{0: BuildResult[], 1: string[]} [results, entries to keep tracking]
     */
    private function RemoveFiles(array $relatives) : array
    {
        $results = [];
        $kept = [];

        foreach ($relatives as $relative)
        {
            if ($this->IsUnsafe($relative))
            {
                $results[] = BuildResult::Failure($relative, 'ignored manifest entry outside the output folder', 'removed');
                continue;
            }

            $absolute = PathHelper::BuildPath([$this->output, $relative]);

            if (!is_file($absolute))
            {
                continue; // already gone
            }

            if (!@unlink($absolute))
            {
                $results[] = BuildResult::Failure($relative, 'could not delete file', 'removed');
                $kept[] = $relative; // retry on the next build
                continue;
            }

            $this->RemoveEmptyParents($absolute);
            $results[] = BuildResult::Removed($relative, $absolute);
        }

        return [$results, $kept];
    }

    /** Guards against a hand-edited manifest pointing outside the output folder. */
    private function IsUnsafe(string $relative) : bool
    {
        $segments = explode('/', str_replace('\\', '/', $relative));

        return $relative === '' || Path::isAbsolute($relative) || in_array('..', $segments, true);
    }

    /** Removes now-empty folders left behind, never the output folder itself. */
    private function RemoveEmptyParents(string $file) : void
    {
        $stop = Path::canonicalize($this->output);
        $dir = Path::getDirectory($file);

        while (str_starts_with($dir, $stop . '/') && is_dir($dir) && count(scandir($dir)) === 2)
        {
            rmdir($dir);
            $dir = Path::getDirectory($dir);
        }
    }
}