<?php

namespace Jemer\Tiny\Commands;

use Jemer\Tiny\Dev\DevServer;
use Jemer\Tiny\Dev\FileWatcher;
use Jemer\Tiny\Dev\Rebuilder;
use Jemer\Tiny\Helpers\Paths;
use Jemer\Tiny\Loaders\ConfigLoader;
use Override;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\SignalableCommandInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Path;

/** Shared behaviour of the long-running commands: `watch` and `serve`. */
abstract class LiveCommand extends Command implements SignalableCommandInterface
{
    private const POLL_MICROSECONDS = 500_000;
    private const SETTLE_MICROSECONDS = 150_000; // let editors finish writing before building

    private bool $running = true;

    #[Override]
    public function getSubscribedSignals() : array
    {
        // Without the pcntl extension (e.g. Windows) Ctrl+C simply ends the process
        return function_exists('pcntl_signal') ? [SIGINT, SIGTERM] : [];
    }

    #[Override]
    public function handleSignal(int $signal, int|false $previousExitCode = 0) : int|false
    {
        $this->running = false;

        return false; // don't exit here, let the loop stop and clean up
    }

    protected function Rebuild(SymfonyStyle $io) : void
    {
        $result = (new Rebuilder())->Run();
        $time = date('H:i:s');

        foreach ($result['errors'] as $error)
        {
            $io->writeln("<error>error</error> {$error}");
        }

        if ($result['errors'] !== [])
        {
            $count = count($result['errors']);
            $io->writeln("[{$time}] <comment>finished with {$count} error(s); fix and save to rebuild</comment>");
            return;
        }

        $removed = $result['removed'] > 0 ? ", {$result['removed']} stale removed" : '';
        $io->writeln("[{$time}] <info>built</info> {$result['built']} files{$removed} ({$result['ms']} ms)");
    }

    /**
     * Blocks until Ctrl+C (or the server dies).
     *
     * @param bool $watch rebuild when files change
     */
    protected function Loop(SymfonyStyle $io, bool $watch, ?DevServer $server) : int
    {
        $watcher = new FileWatcher(fn() => $this->WatchTargets());
        $snapshot = $watcher->Snapshot();

        if ($watch)
        {
            $this->ReportTargets($io);
        }

        $io->writeln('Press Ctrl+C to stop.');
        $exitCode = Command::SUCCESS;

        while ($this->running)
        {
            usleep(self::POLL_MICROSECONDS);

            if ($server !== null && !$server->IsRunning())
            {
                $io->error('The server stopped unexpectedly.');
                $exitCode = Command::FAILURE;
                break;
            }

            if (!$watch)
            {
                continue;
            }

            if (FileWatcher::Changes($snapshot, $watcher->Snapshot()) === [])
            {
                continue;
            }

            usleep(self::SETTLE_MICROSECONDS);

            $settled = $watcher->Snapshot();
            $changes = FileWatcher::Changes($snapshot, $settled);
            $snapshot = $settled; // taken before building, so edits made during a build trigger another one

            if ($changes === [])
            {
                continue;
            }

            $this->ReportChanges($io, $changes);

            $targets = $this->WatchTargets();
            $this->Rebuild($io);

            // A config change can move what we watch (e.g. a different theme).
            // Start from a fresh snapshot, or the new folder's files look "added".
            if ($this->WatchTargets() !== $targets)
            {
                $snapshot = $watcher->Snapshot();
                $this->ReportTargets($io);
            }
        }

        $io->writeln('Stopped.');

        return $exitCode;
    }

    private function ReportTargets(SymfonyStyle $io) : void
    {
        $names = array_map(fn(string $path) => Path::makeRelative($path, Paths::Root()), $this->WatchTargets());

        $io->writeln('Watching ' . implode(', ', $names));
    }

    /** @return string[] */
    private function WatchTargets() : array
    {
        return [
            Paths::ConfigFile(),
            Paths::Get('content'),
            Paths::Get('templates', (string) ConfigLoader::Get('theme.name')),
            Paths::Get('assets'),
        ];
    }

    /** @param array<string, string> $changes */
    private function ReportChanges(SymfonyStyle $io, array $changes) : void
    {
        $time = date('H:i:s');
        $shown = 0;

        foreach ($changes as $path => $type)
        {
            if ($shown++ === 5)
            {
                $io->writeln('           ... and ' . (count($changes) - 5) . ' more');
                break;
            }

            $io->writeln("[{$time}] {$type} " . Path::makeRelative($path, Paths::Root()));
        }
    }
}