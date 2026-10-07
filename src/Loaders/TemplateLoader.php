<?php

namespace Jemer\Tiny\Loaders;

use Jemer\Tiny\Twig\AssetExtension;
use Jemer\Tiny\Helpers\Paths;
use Twig\Environment;
use Symfony\Component\Finder\Finder;
use Twig\Loader\FilesystemLoader;

class TemplateLoader
{
    private Environment $twig;

    public function __construct()
    {
        $loader = new FilesystemLoader(
            Paths::Get('templates', ConfigLoader::Get('theme.name'))
        );

        $this->twig = new Environment($loader, [
            'cache' => false
        ]);

        $this->twig->addGlobal('site', ConfigLoader::Get('site', []));
        $this->twig->addExtension(new AssetExtension());
    }

    public function render(string $template, array $args) : string
    {
        return $this->twig->render($template, $args);
    }

    /**
     * Names of the theme templates a page can use, e.g. ['page'].
     * Skips the base layout and the list template, which need different data.
     *
     * @return string[]
     */
    public static function PageTemplates() : array
    {
        $directory = Paths::Get('templates', ConfigLoader::Get('theme.name'));

        if (!is_dir($directory))
        {
            return [];
        }

        $skip = ['base', ConfigLoader::Get('list.template', 'list')];
        $names = [];

        foreach ((new Finder())->files()->in($directory)->depth(0)->name('*.html.twig') as $file)
        {
            $name = substr($file->getFilename(), 0, -strlen('.html.twig'));

            if (!in_array($name, $skip, true))
            {
                $names[] = $name;
            }
        }

        sort($names);

        return $names;
    }
}