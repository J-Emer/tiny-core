<?php

namespace Jemer\Tiny\Commands;

use Jemer\Tiny\Generators\OutputCleaner;
use Override;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CleanCommand extends Command
{
    #[Override]
    protected function configure() : void
    {
        $this->setName('clean');
        $this->setDescription('Removes every file Tiny has generated (other files in the output folder are left alone)');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new SymfonyStyle($input, $output);
        $removed = 0;
        $failed = 0;

        foreach ((new OutputCleaner())->RemoveAll() as $result)
        {
            if ($result->ok)
            {
                $removed++;
                $io->writeln("<comment>removed</comment> {$result->name}");
            }
            else
            {
                $failed++;
                $io->writeln("<error>failed</error>  {$result->name}: {$result->message}");
            }
        }

        if ($failed > 0)
        {
            $io->warning("{$removed} removed, {$failed} failed");
            return Command::FAILURE;
        }

        $removed === 0 ? $io->writeln('Nothing to clean.') : $io->success("{$removed} files removed");
        return Command::SUCCESS;
    }
}