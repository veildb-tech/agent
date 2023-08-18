<?php

declare(strict_types=1);

namespace App\Service\Methods;

use App\Service\AppConfig;
use App\Service\ShellProcess;

abstract class AbstractMethod implements MethodInterface
{

    /**
     * @param AppConfig $appConfig
     * @param ShellProcess $shellProcess
     */
    public function __construct(
        protected readonly AppConfig $appConfig,
        protected readonly ShellProcess $shellProcess,
    ) {
    }

    /**
     * @param string $dbUuid
     * @param string $filename
     * @return string
     */
    protected function getOriginFile(string $dbUuid, string $filename): string
    {
        return $this->appConfig->getDumpUntouchedDirectory() . '/' . $dbUuid . '/' . $filename;
    }
}
