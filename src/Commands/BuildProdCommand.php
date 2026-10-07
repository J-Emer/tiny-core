<?php

namespace Jemer\Tiny\Commands;

use Jemer\Tiny\Loaders\ConfigLoader;
use Override;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class BuildProdCommand extends BuildAllCommand
{
    #[Override]
    protected function configure() : void
    {
        // Deliberately not calling parent::configure(): a deploy build always cleans
        $this->setName('build:prod');
        $this->setDescription('Builds the site for deployment, using site.production_url for every link');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new SymfonyStyle($input, $output);
        $url = trim((string) ConfigLoader::Get('site.production_url', ''));

        if ($url === '')
        {
            $io->error('site.production_url is not set in config.yaml (for example: production_url: https://example.com)');
            return Command::FAILURE;
        }

        if (!filter_var($url, FILTER_VALIDATE_URL) || !in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true))
        {
            $io->error("site.production_url must be a full URL starting with http:// or https:// (got '{$url}')");
            return Command::FAILURE;
        }

        $url = rtrim($url, '/');
        ConfigLoader::Override('site.baseurl', $url);

        $io->writeln("Building for <info>{$url}</info>");

        return $this->Build($io, true);
    }
}