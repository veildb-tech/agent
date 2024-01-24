<?php

declare(strict_types=1);

namespace App\Service\Methods;

use App\Service\AppConfig;
use App\Service\Security\Encryptor;
use App\Service\ShellProcess;

abstract class AbstractMethod implements MethodInterface
{
    /**
     * @param AppConfig $appConfig
     * @param ShellProcess $shellProcess
     * @param Encryptor $encryptor
     */
    public function __construct(
        protected readonly AppConfig $appConfig,
        protected readonly ShellProcess $shellProcess,
        protected readonly Encryptor $encryptor
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

    /**
     * Retrieve destination file
     *
     * @param string $dbUuid
     * @param string|null $filename
     * @return string
     */
    protected function getDestinationFile(string $dbUuid, ?string $filename): string
    {
        if (!$filename) {
            $filename = time() . '.sql';
        }

        return $this->getOriginFile($dbUuid, $filename);
    }

    /**
     * For now support everything
     *
     * @param string $engine
     *
     * @return bool
     */
    public function support(string $engine): bool
    {
        return true;
    }

    /**
     * Get host for connection to DB engine
     *
     * @param array $connConfig
     *
     * @return string
     */
    public function getConnectionHost(array $connConfig): string
    {
        if ($this->appConfig->isDockerUsed() && $connConfig['db_host'] === 'localhost') {
            return $this->appConfig->getDockerGateway();
        }
        return $connConfig['db_host'];
    }

    static function validateRequired($value)
    {
        if (empty($value)) {
            throw new \RuntimeException('Value is required.');
        }

        return $value;
    }
}
