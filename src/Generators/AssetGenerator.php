<?php

namespace Jemer\Tiny\Generators;

use Jemer\Tiny\Helpers\PathHelper;
use Jemer\Tiny\Helpers\Paths;
use Jemer\Tiny\Models\BuildResult;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class AssetGenerator
{
    private string $original;
    private string $output;

    public function __construct()
    {
        $this->original = Paths::Get('assets');
        $this->output = Paths::Get('assets_output');
    }

    public function Build() : BuildResult
    {
        if (!is_dir($this->original))
        {
            return BuildResult::Failure('assets', "assets folder not found: {$this->original}");
        }

        (new Filesystem())->mirror($this->original, $this->output);

        // mirror() copies dotfiles too, so list them as well
        $finder = (new Finder())->files()->in($this->original)->ignoreDotFiles(false)->ignoreVCS(false);

        $files = [];
        foreach ($finder as $file)
        {
            $files[] = PathHelper::BuildPath([$this->output, $file->getRelativePathname()]);
        }

        return BuildResult::Success('assets', $this->output, $files);
    }
}