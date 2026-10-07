<?php

namespace Jemer\Tiny\Commands;

use Jemer\Tiny\Generators\PageGenerator;
use Override;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class BuildSingleCommand extends Command
{
    #[Override]
    protected function configure() : void
    {
        $this->setName('build:single');
        $this->setDescription('Builds a single content file');
        $this->addArgument('slug', InputArgument::REQUIRED, 'Slug of the page to build');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new SymfonyStyle($input, $output);
        $result = (new PageGenerator())->BuildSingle($input->getArgument('slug'));

        if (!$result->ok)
        {
            $io->error($result->message);
            return Command::FAILURE;
        }

        $io->success("built {$result->name} -> {$result->path}");
        return Command::SUCCESS;
    }
}
