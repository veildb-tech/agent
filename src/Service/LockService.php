<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;

class LockService
{
    public const FILE_PATH = 'var/processing.flag';

    /**
     * @param Filesystem $filesystem
     * @param AppConfig $appConfig
     */
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly AppConfig $appConfig
    ) {
    }

    public function lock(): void
    {
        $this->filesystem->touch($this->getFilePath());
    }

    public function unlock(): void
    {
        $this->filesystem->remove($this->getFilePath());
    }

    public function isLocked(): bool
    {
        return $this->filesystem->exists($this->getFilePath());
    }

    private function getFilePath(): string
    {
        return rtrim($this->appConfig->getProjectDir(), '/') . '/' . self::FILE_PATH;
    }
}
