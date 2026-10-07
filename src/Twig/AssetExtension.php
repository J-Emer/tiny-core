<?php

namespace Jemer\Tiny\Twig;

use Jemer\Tiny\Helpers\Paths;
use Jemer\Tiny\Loaders\ConfigLoader;
use RuntimeException;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AssetExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('assets', [$this, 'Assets']),
            new TwigFunction('css', [$this, 'Css']),
            new TwigFunction('js', [$this, 'JS']),
            new TwigFunction('images', [$this, 'Images']),
            new TwigFunction('fonts', [$this, 'Fonts']),
        ];
    }

    /** assets('/css/style.css') => {baseurl}/assets/css/style.css */
    public function Assets(string $path): string
    {
        return $this->AssetPath($path);
    }

    public function Css(string $path) : string
    {
        return $this->AssetPath('css/' . ltrim($path));
    }

    public function JS(string $path) : string
    {
        return $this->AssetPath('js/' . ltrim($path));
    }    

    public function Images(string $path) : string
    {
        return $this->AssetPath('images/' . ltrim($path));
    }

    public function Fonts(string $path) : string
    {
        return $this->AssetPath('fonts/' . ltrim($path));
    }

    private function AssetPath(string $path) : string
    {
        $path = ltrim($path, '/');

        $source = Paths::Get('assets', $path);

        // if(!file_exists($source))
        // {
        //     throw new RuntimeException("Asset not found: {$source}");
        // }

        // if(is_dir($source))
        // {
        //     throw new RuntimeException("Asset path is a directory, expected a file: {$source}");
        // }

        return rtrim((string) ConfigLoader::Get('site.baseurl', ''), '/')
            . '/assets/'
            . ltrim($path, '/');
    }
}
