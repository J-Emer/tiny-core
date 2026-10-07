<?php

namespace Jemer\Tiny\Twig;

use Jemer\Tiny\Loaders\ConfigLoader;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AssetExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('assets', [$this, 'Assets']),
        ];
    }

    /** assets('/css/style.css') => {baseurl}/assets/css/style.css */
    public function Assets(string $path): string
    {
        return rtrim((string) ConfigLoader::Get('site.baseurl', ''), '/')
            . '/assets/'
            . ltrim($path, '/');
    }
}
