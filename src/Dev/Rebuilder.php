<?php

namespace Jemer\Tiny\Dev;

use Jemer\Tiny\Generators\PageGenerator;
use Jemer\Tiny\Helpers\Paths;
use Jemer\Tiny\Loaders\ConfigLoader;
use Throwable;

/**
 * Runs a full build from scratch: reloads config.yaml and creates fresh
 * loaders, so edits made since the last build are picked up. Never throws,
 * because a typo in a content file must not stop a running watcher.
 */
class Rebuilder
{
    /**
     * @return array{built: int, removed: int, errors: string[], ms: int}
     */
    public function Run() : array
    {
        $start = hrtime(true);
        $built = 0;
        $removed = 0;
        $errors = [];

        try
        {
            ConfigLoader::Load(Paths::ConfigFile());

            foreach ((new PageGenerator())->BuildAll() as $result)
            {
                if (!$result->ok)
                {
                    $errors[] = "{$result->name}: {$result->message}";
                }
                elseif ($result->action === 'removed')
                {
                    $removed++;
                }
                else
                {
                    $built++;
                }
            }
        }
        catch (Throwable $e)
        {
            $errors[] = $e->getMessage();
        }

        return [
            'built'   => $built,
            'removed' => $removed,
            'errors'  => $errors,
            'ms'      => (int) round((hrtime(true) - $start) / 1_000_000),
        ];
    }
}
