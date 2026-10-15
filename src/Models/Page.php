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
        public bool $nav = false,
        public int $order = 10,
        public array $tags = [],
        public string $thumb = '',
        public string $content = '',
        public string $url,
        public int $wordcount,
        public string $blurb,
        public string $readingtime
    ) {}



    public function __toString(): string
    {
        return json_encode([
            'title'       => $this->title,
            'slug'        => $this->slug,
            'date'        => $this->date->format(DateTimeImmutable::ATOM),
            'template'    => $this->template,
            'draft'       => $this->draft,
            'nav'         => $this->nav,
            'order'       => $this->order,
            'tags'        => $this->tags,
            'thumb'       => $this->thumb,
            'content'     => $this->content,
            'url'         => $this->url,
            'blurb'       => $this->blurb,
            'wordcoune'   => $this->wordcount,
            'readingtime' =>$this->readingtime
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '';
    }


}