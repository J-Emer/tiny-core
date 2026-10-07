<?php

namespace Jemer\Tiny\Commands;

use Jemer\Tiny\Creators\PageCreator;
use Jemer\Tiny\Helpers\PathHelper;
use Jemer\Tiny\Helpers\Paths;
use Jemer\Tiny\Loaders\TemplateLoader;
use Override;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Path;

class CreatePageCommand extends Command
{
    #[Override]
    protected function configure() : void
    {
        $this->setName('create:page');
        $this->setDescription('Creates a new content page (prompts for anything you don\'t pass as an option)');

        $this->addArgument('title', InputArgument::OPTIONAL, 'Page title');
        $this->addOption('slug', null, InputOption::VALUE_REQUIRED, 'URL slug (default: made from the title)');
        $this->addOption('template', null, InputOption::VALUE_REQUIRED, 'Template name, e.g. page');
        $this->addOption('nav', null, InputOption::VALUE_NEGATABLE, 'Show in the navigation (--nav / --no-nav)');
        $this->addOption('order', null, InputOption::VALUE_REQUIRED, 'Position in the navigation, lower comes first');
        $this->addOption('draft', null, InputOption::VALUE_NEGATABLE, 'Mark as a draft (--draft / --no-draft)');
        $this->addOption('tags', null, InputOption::VALUE_REQUIRED, 'Comma-separated tags');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new SymfonyStyle($input, $output);
        $ask = $input->isInteractive();

        try
        {
            $title = $this->GetTitle($io, $input, $ask);
            $slug = $this->GetSlug($io, $input, $ask, $title);
            $template = $this->GetTemplate($io, $input, $ask);
            $nav = $input->getOption('nav') ?? ($ask ? $io->confirm('Show in the navigation?', true) : true);
            $order = $this->GetOrder($io, $input, $ask, $nav);
            $draft = $input->getOption('draft') ?? ($ask ? $io->confirm('Save as a draft? (builds skip drafts)', false) : false);
            $tags = $this->GetTags($io, $input, $ask);

            $path = PageCreator::Create([
                'title'    => $title,
                'slug'     => $slug,
                'template' => $template,
                'nav'      => $nav,
                'order'    => $order,
                'draft'    => $draft,
                'tags'     => $tags,
            ]);
        }
        catch (RuntimeException $e)
        {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }

        $io->success('Created ' . Path::makeRelative($path, Paths::Root()));
        $io->definitionList(
            ['Title'      => $title],
            ['Slug'       => $slug],
            ['Template'   => $template],
            ['Navigation' => $nav ? "yes (order {$order})" : 'no'],
            ['Draft'      => $draft ? 'yes' : 'no'],
            ['Tags'       => $tags === [] ? '-' : implode(', ', $tags)]
        );
        $io->writeln($draft
            ? 'Drafts are skipped by builds until you set draft: false.'
            : "Build it with: build:single {$slug}");

        return Command::SUCCESS;
    }

    private function GetTitle(SymfonyStyle $io, InputInterface $input, bool $ask) : string
    {
        $title = $input->getArgument('title');

        if ($title === null && $ask)
        {
            $title = $io->ask('Title', null, fn($v) => $this->ValidateTitle($v));
        }

        return $this->FormatTitle($this->ValidateTitle($title));
    }

    private function GetSlug(SymfonyStyle $io, InputInterface $input, bool $ask, string $title) : string
    {
        $slug = $input->getOption('slug');

        if ($slug === null)
        {
            $suggested = PathHelper::Stringify($title);
            $slug = $ask
                ? $io->ask('Slug', $suggested !== '' ? $suggested : null, fn($v) => $this->ValidateSlug($v))
                : $suggested;
        }

        return $this->ValidateSlug($slug);
    }

    private function GetTemplate(SymfonyStyle $io, InputInterface $input, bool $ask) : string
    {
        $templates = TemplateLoader::PageTemplates();

        if ($templates === [])
        {
            throw new RuntimeException('No page templates found in the theme folder.');
        }

        $template = $input->getOption('template');

        if ($template === null)
        {
            $default = in_array('page', $templates, true) ? 'page' : $templates[0];

            // Only ask when there is an actual choice to make
            $template = ($ask && count($templates) > 1)
                ? $io->askQuestion(new ChoiceQuestion('Template', $templates, array_search($default, $templates, true)))
                : $default;
        }

        if (!in_array($template, $templates, true))
        {
            throw new RuntimeException("Unknown template '{$template}'. Available: " . implode(', ', $templates));
        }

        return $template;
    }

    private function GetOrder(SymfonyStyle $io, InputInterface $input, bool $ask, bool $nav) : int
    {
        $order = $input->getOption('order');

        if ($order === null && $nav && $ask)
        {
            $order = $io->ask('Navigation order (lower comes first)', '10', fn($v) => $this->ValidateOrder($v));
        }

        return $this->ValidateOrder($order ?? '10');
    }

    private function GetTags(SymfonyStyle $io, InputInterface $input, bool $ask) : array
    {
        $tags = $input->getOption('tags');

        if ($tags === null && $ask)
        {
            $tags = $io->ask('Tags (comma separated, optional)');
        }

        $list = array_filter(array_map('trim', explode(',', (string) $tags)), fn($t) => $t !== '');

        return array_values(array_unique($list));
    }

    /**
     * Capitalizes the first letter of every word. The rest of each word is left
     * as typed, so "PHP" stays "PHP" and "don't" doesn't become "Don'T".
     */
    private function FormatTitle(string $title) : string
    {
        return preg_replace_callback(
            '/(?<![\p{L}\p{N}\'’])\p{Ll}/u',
            fn(array $match) => mb_strtoupper($match[0]),
            $title
        ) ?? $title;
    }

    private function ValidateTitle(?string $value) : string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : throw new RuntimeException('A title is required.');
    }

    private function ValidateSlug(?string $value) : string
    {
        $value = trim((string) $value);

        return PageCreator::SlugError($value) === null
            ? $value
            : throw new RuntimeException(PageCreator::SlugError($value));
    }

    private function ValidateOrder(?string $value) : int
    {
        $order = filter_var(trim((string) $value), FILTER_VALIDATE_INT);

        return $order !== false ? $order : throw new RuntimeException('The order must be a whole number.');
    }
}