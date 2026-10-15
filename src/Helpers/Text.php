<?php

declare(strict_types=1);

namespace Jemer\Tiny\Helpers;

use Jemer\Tiny\Loaders\ConfigLoader;
use Symfony\Component\VarDumper\VarDumper;

final class Text
{
    public static function readingTime(string $text) : int
    {
        if(empty($text))
        {
            return 0;
        }

        $cleanedText = strip_tags($text);
        $wordCount = str_word_count($cleanedText);

        $rate = (int)ConfigLoader::Get('page.words_per_minute');

        return (int)max(1, ceil($wordCount / $rate));
    }
    public static function plain(string $html): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }

    public static function wordCount(string $text): int
    {
        return (int) preg_match_all('/\S+/u', $text);
    }

    /** Truncate on a word boundary and add an ellipsis. */
    public static function truncate(string $text): string
    {
        $length = (int)ConfigLoader::Get('post.truncate');

        if ($length <= 0 || mb_strlen($text) <= $length) {
            return $text;
        }

        $cut = mb_substr($text, 0, $length);
        $space = mb_strrpos($cut, ' ');
        if ($space !== false && $space > $length * 0.6) {
            $cut = mb_substr($cut, 0, $space);
        }

        return rtrim($cut, " \t.,;:-") . '…';
    }

    public static function humanize(string $name): string
    {
        return ucwords(trim(preg_replace('/[-_]+/', ' ', $name) ?? $name));
    }
}
