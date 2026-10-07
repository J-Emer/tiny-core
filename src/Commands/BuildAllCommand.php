<?php

namespace Jemer\Tiny\Commands;

use Jemer\Tiny\Generators\PageGenerator;
use Override;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class BuildAllCommand extends Command
{
    #[Override]
    protected function configure() : void
    {
        $this->setName('build:all');
        $this->setDescription('Builds all of your content files using site.baseurl and removes stale output');
        $this->addOption('no-clean', null, InputOption::VALUE_NONE, 'Keep files left over from earlier builds');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        return $this->Build(new SymfonyStyle($input, $output), !$input->getOption('no-clean'));
    }

    /** The build loop, shared with build:prod. */
    protected function Build(SymfonyStyle $io, bool $clean) : int
    {
        $built = 0;
        $removed = 0;
        $buildFailed = 0;
        $cleanupFailed = 0;

        foreach ((new PageGenerator())->BuildAll($clean) as $result)
        {
            if (!$result->ok)
            {
                $result->action === 'removed' ? $cleanupFailed++ : $buildFailed++;
                $io->writeln("<error>failed</error>  {$result->name}: {$result->message}");
            }
            elseif ($result->action === 'removed')
            {
                $removed++;
                $io->writeln("<comment>removed</comment> {$result->name}");
            }
            else
            {
                $built++;
                $io->writeln("<info>built</info>   {$result->name}");
                $io->writeln("         {$result->path}", OutputInterface::VERBOSITY_VERBOSE);
            }
        }

        $summary = "{$built} files built" . ($removed > 0 ? ", {$removed} stale removed" : '');

        if ($buildFailed > 0 || $cleanupFailed > 0)
        {
            $notes = [];

            if ($buildFailed > 0)
            {
                $notes[] = "{$buildFailed} failed to build (stale-file cleanup skipped)";
            }

            if ($cleanupFailed > 0)
            {
                $notes[] = "{$cleanupFailed} could not be cleaned up";
            }

            $io->warning($summary . ', ' . implode(', ', $notes));
            return Command::FAILURE;
        }

        $io->success($summary);
        return Command::SUCCESS;
    }
}