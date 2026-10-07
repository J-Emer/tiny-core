<?php

namespace Jemer\Tiny\Commands;

use Override;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class WatchCommand extends LiveCommand
{
    #[Override]
    protected function configure() : void
    {
        $this->setName('watch');
        $this->setDescription('Rebuilds the site whenever content, templates, assets or config.yaml change');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new SymfonyStyle($input, $output);

        $this->Rebuild($io);

        return $this->Loop($io, true, null);
    }
}
