<?php

namespace Jemer\Tiny;

use Jemer\Tiny\Commands\BuildAllCommand;
use Jemer\Tiny\Commands\BuildProdCommand;
use Jemer\Tiny\Commands\BuildSingleCommand;
use Jemer\Tiny\Commands\CleanCommand;
use Jemer\Tiny\Commands\CreatePageCommand;
use Jemer\Tiny\Commands\ServeCommand;
use Jemer\Tiny\Commands\ShowCommand;
use Jemer\Tiny\Commands\WatchCommand;
use Jemer\Tiny\Helpers\Paths;
use Jemer\Tiny\Loaders\ConfigLoader;
use RuntimeException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;

/**
 * Boots Tiny for one project.
 *
 * The core (this package) and the project (content, theme, config.yaml) live
 * in different places. The kernel is the one spot that knows about both: it
 * tells the helpers where the project is, loads the core's defaults under the
 * project's config.yaml, and registers the commands.
 */
class Kernel
{
    public const VERSION = 'v0.1.3';

    private ?Application $app = null;

    /** @var Command[] */
    private array $extraCommands = [];

    public function __construct(private readonly string $projectRoot)
    {
    }

    /** The folder this package lives in (where config/defaults.yaml is). */
    public static function CoreRoot() : string
    {
        return dirname(__DIR__);
    }

    /** Register a command of your own. Call before Run(). */
    public function AddCommand(Command $command) : static
    {
        $this->extraCommands[] = $command;

        return $this;
    }

    /** Boots (once) and returns the console application. */
    public function Boot() : Application
    {
        if ($this->app !== null)
        {
            return $this->app;
        }

        Paths::SetRoot($this->projectRoot);

        ConfigLoader::Reset();
        ConfigLoader::SetDefaults(self::CoreRoot() . '/config/defaults.yaml');
        ConfigLoader::Load(Paths::ConfigFile());

        $app = new Application('Tiny', self::VERSION);

        foreach ($this->Commands() as $command)
        {
            $app->addCommand($command);
        }

        return $this->app = $app;
    }

    /** @return int exit code (only returns on a boot failure; the console exits by itself otherwise) */
    public function Run() : int
    {
        try
        {
            $app = $this->Boot();
        }
        catch (RuntimeException $e)
        {
            fwrite(STDERR, 'Tiny: ' . $e->getMessage() . PHP_EOL);

            return Command::FAILURE;
        }

        return $app->run();
    }

    /** @return Command[] */
    private function Commands() : array
    {
        return [
            new BuildAllCommand(),
            new BuildProdCommand(),
            new BuildSingleCommand(),
            new CleanCommand(),
            new CreatePageCommand(),
            new ServeCommand(),
            new WatchCommand(),
            new ShowCommand(),
            ...$this->extraCommands,
        ];
    }
}
