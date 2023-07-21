<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Process\Process;

class ShellProcess
{
    public function run(string $command): void
    {
        $process = Process::fromShellCommandline($command);

        $process->setTimeout(null);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \Exception($process->getErrorOutput());
        }
    }
}
