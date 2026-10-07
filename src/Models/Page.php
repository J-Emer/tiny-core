<?php 

namespace Jemer\Tiny\Models;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;

class Page
{
    public function __construct(
        public string $title,
        public string $slug,
        public DateTimeImmutable $date,
        public string $template,
        public bool $draft = false,
        public bool $nav = true,
        public int $order = 10,
        public array $tags = [],
        public string $thumb = '',
        public string $content = ''
    ) {}

    public static function FromArray(array $arr) : Page
    {
        return new Page(
            (string) ($arr['title'] ?? throw new RuntimeException('Tiny-Core/Models/Page: Missing title')),
            (string) ($arr['slug'] ?? throw new RuntimeException('Tiny-Core/Models/PagePage: Missing slug')),
            self::ParseDate($arr['date'] ?? null),
            (string) ($arr['template'] ?? 'page'),
            filter_var($arr['draft'] ?? false, FILTER_VALIDATE_BOOLEAN),
            filter_var($arr['nav'] ?? true, FILTER_VALIDATE_BOOLEAN),
            (int) ($arr['order'] ?? 10),
            array_map('strval', (array) ($arr['tags'] ?? [])),
            (string)($arr['thumb'] ?? ''),
            (string) ($arr['content'] ?? 'Content goes here.')
        );
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

    public function ToMarkdown(): string
    {
        $frontMatter = [
            'title'    => $this->title,
            'slug'     => $this->slug,
            'date'     => $this->date->format(DateTimeImmutable::ATOM),
            'template' => $this->template,
            'draft'    => $this->draft,
            'nav'      => $this->nav,
            'order'    => $this->order,
            'tags'     => $this->tags,
            'thumb'    => $this->thumb,
        ];

        $yaml = Yaml::dump($frontMatter, 4, 2);

        // Symfony dumps an empty list as "{  }"; "[]" is what people write by hand
        $yaml = preg_replace('/^tags: \{\s*\}$/m', 'tags: []', $yaml);

        return "---\n" . $yaml . "---\n\n" . $this->content;
    }



    public function __toString(): string
    {
        return json_encode([
            'title'    => $this->title,
            'slug'     => $this->slug,
            'date'     => $this->date->format(DateTimeImmutable::ATOM),
            'template' => $this->template,
            'draft'    => $this->draft,
            'nav'      => $this->nav,
            'order'    => $this->order,
            'tags'     => $this->tags,
            'thumb'     => $this->thumb,
            'content'  => $this->content,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '';
    }


}