<?php

namespace Jemer\Tiny\Generators;

use Generator;
use Jemer\Tiny\Helpers\Paths;
use Jemer\Tiny\Loaders\ConfigLoader;
use Jemer\Tiny\Loaders\ContentLoader;
use Jemer\Tiny\Loaders\TemplateLoader;
use Jemer\Tiny\Models\BuildResult;
use Jemer\Tiny\Models\Page;
use Throwable;

class PageGenerator
{
    private ContentLoader $contentLoader;
    private TemplateLoader $templateLoader;
    private AssetGenerator $assetGenerator;
    private OutputCleaner $cleaner;

    public function __construct()
    {
        $this->contentLoader = new ContentLoader();
        $this->templateLoader = new TemplateLoader();
        $this->assetGenerator = new AssetGenerator();
        $this->cleaner = new OutputCleaner();
        $this->EnsureOutputExists();
    }

    private function EnsureOutputExists() : void
    {
        $output = Paths::Get('output');

        if (!is_dir($output))
        {
            mkdir($output, 0777, true);
        }
    }

    /**
     * Yields one BuildResult per item as it is built, so the caller can
     * print progress live. The caller must iterate to run the build.
     *
     * Stale files from earlier builds are removed at the end ($clean), but
     * only if every item built successfully, so a broken template can never
     * delete the last good version of a page.
     *
     * @return Generator<BuildResult>
     */
    public function BuildAll(bool $clean = true) : Generator
    {
        $generated = [];
        $failed = false;

        foreach ($this->BuildSteps() as $result)
        {
            if ($result->ok)
            {
                array_push($generated, ...$result->files);
            }
            else
            {
                $failed = true;
            }

            yield $result;
        }

        foreach ($this->cleaner->Sync($generated, $clean && !$failed) as $result)
        {
            yield $result;
        }
    }

    private function BuildSteps() : Generator
    {
        foreach ($this->contentLoader->GetPages() as $page)
        {
            yield $this->Build($page);
        }

        yield $this->assetGenerator->Build();

        if (ContentLoader::ListEnabled())
        {
            yield $this->BuildList();
        }

        // A 404.md of your own was already built with the other pages
        if (!$this->contentLoader->Has404())
        {
            yield $this->Build404();
        }
    }

    public function BuildSingle(string $slug) : BuildResult
    {
        $page = $this->contentLoader->GetPageBySlug($slug);

        if ($page === null)
        {
            // Without a 404.md the generated 404 page can still be built on its own
            if ($slug !== '404')
            {
                return BuildResult::Failure($slug, "could not find slug: {$slug}");
            }

            $result = $this->Build404();
        }
        else
        {
            $result = $this->Build($page);
        }

        if ($result->ok)
        {
            $this->cleaner->Track($result->files);
        }

        return $result;
    }

    private function Build(Page $page) : BuildResult
    {
        return $this->Render(
            $page->slug,
            $page->template . '.html.twig',
            [
                'page' => $page,
                'current' => $page->slug,
                'navigation' => $this->contentLoader->GetNav()
            ]
        );
    }

    public function BuildList() : BuildResult
    {
        $slug = ContentLoader::ListSlug();

        return $this->Render(
            $slug,
            ConfigLoader::Get('list.template', 'list') . '.html.twig',
            [
                'current' => $slug,
                'categories' => $this->contentLoader->GetTags(),
                'navigation' => $this->contentLoader->GetNav()
            ]
        );
    }

    /**
     * The page for missing URLs: your own 404.md if you have one, otherwise a
     * plain generated page.
     */
    public function Build404() : BuildResult
    {
        $page = $this->contentLoader->GetPageBySlug('404') ?? Page::FromArray([
            'title' => '404',
            'slug' => '404',
            'nav' => false,
            'content' => '<p>Page not found.</p>',
        ]);

        return $this->Build($page);
    }

    private function Render(string $name, string $template, array $args) : BuildResult
    {
        try
        {
            $rendered = $this->templateLoader->render($template, $args);
            $path = Paths::Get('output', $name . '.html');

            if (file_put_contents($path, $rendered) === false)
            {
                return BuildResult::Failure($name, "could not write: {$path}");
            }

            return BuildResult::Success($name, $path);
        }
        catch (Throwable $e)
        {
            return BuildResult::Failure($name, $e->getMessage());
        }
    }
}