<?php

namespace Jemer\Tiny\Commands;

use Jemer\Tiny\Generators\OutputCleaner;
use Jemer\Tiny\Loaders\ContentLoader;
use Override;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ShowCommand extends Command
{
    #[Override]
    protected function configure() : void
    {
        $this->setName('show');
        $this->setDescription('Calls the selected page __toString() and displays it in the console. Primarily used for testing.');
        $this->addArgument('slug', InputArgument::REQUIRED, "Slug of the selected page to show");
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {

        $contentLoader = new ContentLoader();
        $slug = $input->getArgument('slug');
        $page = $contentLoader->GetPageBySlug($slug);
        $output->writeln($page->__toString());

        return Command::SUCCESS;
    }
}