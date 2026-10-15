<?php

declare(strict_types=1);

namespace Jemer\Tiny\Helpers;

use Jemer\Tiny\Helpers\PathHelper;
use Jemer\Tiny\Loaders\ConfigLoader;

final class Slug
{
    public static function slugify(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/[^\p{L}\p{N}]+/u', '-', $text) ?? $text;

        return trim($text, '-') ?: 'untitled';
    }

    public static function toURL(string $slug) : string
    {
        $prettyURL = ConfigLoader::Get('pages.pretty_url');

        $url = $prettyURL 
        ? PathHelper::BuildPath([ConfigLoader::Get('site.baseurl'), $slug, "index.html"], "/")
        : PathHelper::BuildPath([ConfigLoader::Get('site.baseurl'), $slug . ".html"], "/");

        return $url;
    }
}
