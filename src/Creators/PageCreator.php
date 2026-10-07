<?php

namespace Jemer\Tiny\Creators;

use Jemer\Tiny\Helpers\Paths;
use Jemer\Tiny\Loaders\ContentLoader;
use Jemer\Tiny\Models\Page;
use RuntimeException;

class PageCreator
{
    private const SLUG_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    /**
     * Writes content/{slug}.md and returns its absolute path.
     * Never overwrites an existing file.
     *
     * @throws RuntimeException if the slug is invalid or already taken
     */
    public static function Create(array $arr) : string
    {
        $page = Page::FromArray($arr);

        $error = self::SlugError($page->slug);

        if ($error !== null)
        {
            throw new RuntimeException($error);
        }

        $directory = Paths::Get('content');

        if (!is_dir($directory))
        {
            mkdir($directory, 0777, true);
        }

        $path = Paths::Get('content', $page->slug . '.md');

        if (file_put_contents($path, $page->ToMarkdown()) === false)
        {
            throw new RuntimeException("Could not write: {$path}");
        }

        return $path;
    }

    /** Returns why a slug can't be used, or null if it's fine. */
    public static function SlugError(string $slug) : ?string
    {
        if (!preg_match(self::SLUG_PATTERN, $slug))
        {
            return 'Use lowercase letters, numbers and single hyphens only (e.g. my-first-post).';
        }

        if (ContentLoader::ListEnabled() && $slug === ContentLoader::ListSlug())
        {
            return "'{$slug}' is reserved for the list page.";
        }

        if (is_file(Paths::Get('content', $slug . '.md')))
        {
            return "A page with the slug '{$slug}' already exists.";
        }

        return null;
    }
}