<?php 

namespace Jemer\Tiny\Factories;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Jemer\Tiny\Helpers\Slug;
use Jemer\Tiny\Helpers\Text;
use Jemer\Tiny\Models\Page;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;

class Factory
{
    public static function Page(array $arr) : Page
    {
        if(!isset($arr['title']))
        {
            throw new RuntimeException('Tiny-Core/Models/Page: Missing title');
        }

        $content = (string)($arr['content'] ?? 'Content goes here.');
        $slug = $arr['slug'] ?? Slug::slugify($arr['title']);

        return new Page(
            (string) ($arr['title']),
            $slug,
            self::ParseDate($arr['date'] ?? null),
            (string) ($arr['template'] ?? 'page'),
            filter_var($arr['draft'] ?? false, FILTER_VALIDATE_BOOLEAN),
            filter_var($arr['nav'] ?? false, FILTER_VALIDATE_BOOLEAN),
            (int) ($arr['order'] ?? 10),
            array_map('strval', (array) ($arr['tags'] ?? [])),
            (string)($arr['thumb'] ?? ''),
            $content,
            Slug::toURL($slug),
            Text::wordCount($content),
            Text::truncate($content),
            Text::readingTime($content)
        );
    }

    public static function ToMarkdown(Page $page): string
    {
        $frontMatter = [
            'title'    => $page->title,
            'slug'     => $page->slug,
            'date'     => $page->date->format(DateTimeImmutable::ATOM),
            'template' => $page->template,
            'draft'    => $page->draft,
            'nav'      => $page->nav,
            'order'    => $page->order,
            'tags'     => $page->tags,
            'thumb'    => $page->thumb,
        ];

        $yaml = Yaml::dump($frontMatter, 4, 2);

        // Symfony dumps an empty list as "{  }"; "[]" is what people write by hand
        $yaml = preg_replace('/^tags: \{\s*\}$/m', 'tags: []', $yaml);

        return "---\n" . $yaml . "---\n\n" . $page->content;
    }

    private static function ParseDate(mixed $value) : DateTimeImmutable
    {
        if ($value === null)
        {
            return new DateTimeImmutable();
        }

        if ($value instanceof DateTimeInterface)
        {
            return DateTimeImmutable::createFromInterface($value);
        }

        // Symfony's YAML parser turns unquoted timestamps into unix ints
        if (is_int($value) || is_float($value) || (is_string($value) && is_numeric($value)))
        {
            $date = DateTimeImmutable::createFromFormat('U.u', sprintf('%.6f', $value));

            if ($date === false)
            {
                throw new RuntimeException("Page: Invalid date: {$value}");
            }

            return $date->setTimezone(new DateTimeZone(date_default_timezone_get()));
        }

        try
        {
            return new DateTimeImmutable((string) $value);
        }
        catch (\Exception)
        {
            throw new RuntimeException("Page: Invalid date: {$value}");
        }
    }    
}