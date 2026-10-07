<?php

namespace Jemer\Tiny\Models;

use Jemer\Tiny\Loaders\ConfigLoader;

class NavLink
{
    public function __construct(
        public string $title,
        public string $slug,
        public string $url,
        public int $order = 10,
    ) {}

    public static function Create(string $title, string $slug, int $order = 10) : NavLink
    {
        $baseUrl = rtrim((string) ConfigLoader::Get('site.baseurl', ''), '/');

        return new NavLink($title, $slug, $baseUrl . '/' . $slug . '.html', $order);
    }

    public static function FromPage(Page $page) : NavLink
    {
        return self::Create($page->title, $page->slug, $page->order);
    }

    public function __toString(): string
    {
        return json_encode([
            'title' => $this->title,
            'slug'  => $this->slug,
            'url'   => $this->url,
            'order' => $this->order,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '';
    }
}
