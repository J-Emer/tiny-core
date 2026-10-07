<?php

namespace Jemer\Tiny\Dev;

use RuntimeException;

/** Runs PHP's built-in web server as a child process. */
class DevServer
{
    /** @var resource|null */
    private $process = null;

    /**
     * @throws RuntimeException if the port is busy or the server fails to start
     */
    public function Start(string $host, int $port, string $docRoot, bool $verbose = false) : void
    {
        $probe = @stream_socket_server("tcp://{$host}:{$port}", $errno, $error);

        if ($probe === false)
        {
            throw new RuntimeException(
                "Cannot listen on {$host}:{$port} ({$error}). Is something else using it? Try --port=" . ($port + 1)
            );
        }

        fclose($probe);

        if (!is_dir($docRoot))
        {
            mkdir($docRoot, 0777, true);
        }

        $devNull = PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null';
        $log = $verbose ? STDOUT : ['file', $devNull, 'w'];

        $process = proc_open(
            [PHP_BINARY, '-S', "{$host}:{$port}", '-t', $docRoot],
            [0 => ['file', $devNull, 'r'], 1 => $log, 2 => $log],
            $pipes
        );

        if (!is_resource($process))
        {
            throw new RuntimeException("Could not start PHP's built-in server.");
        }

        $this->process = $process;

        // Wait (up to 3 seconds) until it actually accepts connections
        for ($attempt = 0; $attempt < 30; $attempt++)
        {
            if (!$this->IsRunning())
            {
                $this->Stop();
                throw new RuntimeException('The server exited immediately. Run with -v to see its output.');
            }

            $socket = @fsockopen($host, $port, $errno, $error, 0.2);

            if ($socket !== false)
            {
                fclose($socket);
                return;
            }

            usleep(100_000);
        }

        $this->Stop();
        throw new RuntimeException("The server did not start listening on {$host}:{$port}.");
    }

    public function IsRunning() : bool
    {
        return is_resource($this->process) && proc_get_status($this->process)['running'];
    }

    public function Stop() : void
    {
        if (is_resource($this->process))
        {
            proc_terminate($this->process);
            proc_close($this->process);
        }

        $this->process = null;
    }

    public function __destruct()
    {
        $this->Stop();
    }
}
