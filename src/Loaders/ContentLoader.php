<?php

namespace Jemer\Tiny\Loaders;

use Jemer\Tiny\Helpers\Paths;
use Jemer\Tiny\Models\NavLink;
use Jemer\Tiny\Models\Page;
use Jemer\Tiny\Parsers\JsonParser;
use Jemer\Tiny\Parsers\MarkdownParser;
use Jemer\Tiny\Parsers\YamlParser;
use Mni\FrontYAML\Parser;
use RuntimeException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Throwable;

class ContentLoader
{
    private array $pages = [];
    private array $nav = [];
    private array $tags = [];
    private string $contentDirectory;
    private Parser $parser;


    private array $parsers;



    public function __construct()
    {
        $this->parsers = [
            "md" => new MarkdownParser(), 
            "json" => new JsonParser(),
            "yaml" => new YamlParser()
        ];
        $this->contentDirectory = Paths::Get('content');
        $this->parser = new Parser();
        $this->LoadFiles();
    }

    private function LoadFiles() : void
    {
        $finder = new Finder();
        $finder->files()->in($this->contentDirectory)->name(['*.md', '*.json', '*.yaml']);

        foreach ($finder as $file)
        {
            try
            {
                $page = Page::FromArray($this->ParseFile($file));
            }
            catch (Throwable $e)
            {
                // Say which file is broken, not just what is wrong with it
                throw new RuntimeException($file->getRelativePathname() . ': ' . $e->getMessage(), 0, $e);
            }

            if ($page->draft)
            {
                continue;
            }

            if (self::ListEnabled() && $page->slug === self::ListSlug())
            {
                throw new RuntimeException("Slug '{$page->slug}' is reserved for the list page");
            }

            $this->pages[] = $page;
            $this->AddTags($page);

            if ($page->nav)
            {
                $this->nav[] = NavLink::FromPage($page);
            }
        }

        if (self::ListEnabled())
        {
            $this->nav[] = NavLink::Create(
                ConfigLoader::Get('list.title', 'List'),
                self::ListSlug(),
                ConfigLoader::Get('list.order', 10)
            );
        }

        usort($this->nav, fn(NavLink $a, NavLink $b) => $a->order <=> $b->order);
    }

    public static function ListSlug() : string
    {
        return ConfigLoader::Get('list.slug', 'list');
    }



    private function ParseFile(SplFileInfo $file) : array
    {
        // 1. Note: SplFileInfo::getExtension() returns 'md', not '.md'
        $extension = $file->getExtension(); 

        // 2. Check if a parser exists for this extension directly
        if (isset($this->parsers[$extension])) {
            echo $extension . PHP_EOL;
            // 3. Call the parse method dynamically on the object
            return $this->parsers[$extension]->parse($file);
        }

        return []; 

        // --- original code, not used ---
        // $document = $this->parser->parse($file->getContents());
        // $yaml = $document->getYAML() ?? [];
        // $yaml['content'] = $document->getContent();
        // return $yaml;
    }

    private function AddTags(Page $page) : void
    {
        $navLink = NavLink::FromPage($page);

        foreach ($page->tags as $tag)
        {
            $this->tags[$tag][] = $navLink;
        }
    }

    public function GetPages() : array
    {
        return $this->pages;
    }

    public function GetNav() : array
    {
        return $this->nav;
    }

    public function GetTags() : array
    {
        return $this->tags;
    }

    public function GetPageBySlug(string $slug) : ?Page
    {
        foreach ($this->pages as $page)
        {
            if ($page->slug === $slug)
            {
                return $page;
            }
        }

        return null;
    }

    public function Has404() : bool
    {
        return $this->GetPageBySlug('404') !== null;
    }

    public static function ListEnabled() : bool
    {
        return filter_var(ConfigLoader::Get('list.build', true), FILTER_VALIDATE_BOOLEAN);
    }

    public function __toString() : string
    {
        return json_encode([
            'pages' => $this->pages,
            'nav'   => $this->nav,
            'tags'  => $this->tags,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '';
    }
}
