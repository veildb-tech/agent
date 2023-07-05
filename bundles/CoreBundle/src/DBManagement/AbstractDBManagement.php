<?php

declare(strict_types=1);

namespace DbManager\CoreBundle\DBManagement;

use App\Service\AppConfig;
use DbManager\CoreBundle\Exception\ShellProcessorException;
use DbManager\CoreBundle\Interfaces\DbDataManagerInterface;
use Symfony\Component\Process\Process;

/**
 * Abstract
 */
abstract class AbstractDBManagement implements DBManagementInterface
{
    public function __construct(
        protected readonly AppConfig $appConfig
    ) {
    }

    /**
     * @inheritdoc
     */
    public function create(DbDataManagerInterface $database): bool
    {
        $command = $this->getCreateLine($database->getName());

        $this->execute($command);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function drop(DbDataManagerInterface $database): bool
    {
        $command = $this->getDropLine($database->getName());

        $this->execute($command);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function dump(DbDataManagerInterface $database): string
    {
        $command = $this->getDumpLine($database->getName(), $database->getBackupPath());

        return $this->execute($command);
    }

    /**
     * @inheritdoc
     */
    public function import(DbDataManagerInterface $database): string
    {
        $command = $this->getImportLine($database->getName(), $database->offsetGet('inputFile'));

        return $this->execute($command);
    }

    /**
     * Execute process command
     *
     * @param string $command
     *
     * @return string
     * @throws ShellProcessorException
     */
    protected function execute(string $command): string
    {
        $process = Process::fromShellCommandline($command);
        $process->setTimeout(null);

        $process->run();
        if (!$process->isSuccessful()) {
            throw new ShellProcessorException($process->getErrorOutput());
        }

        return $process->getOutput();
    }

    /**
     * Get DB password
     *
     * @return string
     */
    protected function getPassword(): string
    {
        return $this->appConfig->getConfig('work_db_password')
            ? $this->appConfig->getConfig('work_db_password')
            : '';
    }
}
