<?php

namespace Jemer\Tiny\Commands;

use Jemer\Tiny\Dev\DevServer;
use Jemer\Tiny\Helpers\Paths;
use Jemer\Tiny\Loaders\ConfigLoader;
use Override;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ServeCommand extends LiveCommand
{
    #[Override]
    protected function configure() : void
    {
        $this->setName('serve');
        $this->setDescription('Builds the site, serves it locally and rebuilds when files change');
        $this->addOption('host', null, InputOption::VALUE_REQUIRED, 'Host to listen on', 'localhost');
        $this->addOption('port', null, InputOption::VALUE_REQUIRED, 'Port to listen on', '8000');
        $this->addOption('no-watch', null, InputOption::VALUE_NONE, 'Serve only, don\'t rebuild on changes');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new SymfonyStyle($input, $output);
        $host = (string) $input->getOption('host');
        $port = filter_var($input->getOption('port'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]);

        if ($port === false)
        {
            $io->error('The port must be a number between 1 and 65535.');
            return self::FAILURE;
        }

        // Links in the built pages are absolute, so for the local server they
        // must point at the address we are actually serving on.
        ConfigLoader::Override('site.baseurl', "http://{$host}:{$port}");

        $this->Rebuild($io);

        $server = new DevServer();

        try
        {
            $server->Start($host, $port, Paths::Get('output'), $output->isVerbose());
        }
        catch (RuntimeException $e)
        {
            $io->error($e->getMessage());
            return self::FAILURE;
        }

        $io->success("Serving http://{$host}:{$port}");
        $io->writeln('Pages are built with local links. Run build:prod before deploying.');

        try
        {
            return $this->Loop($io, !$input->getOption('no-watch'), $server);
        }
        finally
        {
            $server->Stop();
        }
    }
}