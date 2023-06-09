<?php

declare(strict_types=1);

namespace DbManager\CoreBundle\DumpProcessor;

use DbManager\CoreBundle\Exception\ShellProcessorException;
use DbManager\CoreBundle\Interfaces\DbDataManagerInterface;
use Symfony\Component\Process\Process;

/**
 * Mysql Processor instance
 */
abstract class AbstractDumpProcessor implements DumpProcessorInterface
{
    /**
     * @inheritdoc
     */
    public function execute(DbDataManagerInterface $database): string
    {
        $command = $this->getDumpCommandLine($database->getName(), '.');

        $process = new Process([$command]);
        $process->setTimeout(null);

        $process->run();
        if (!$process->isSuccessful()) {
            throw new ShellProcessorException($process->getErrorOutput());
        }

        return $process->getOutput();
    }

    /**
     * Get dump command line
     *
     * @param string $dbName
     * @param string $outputPath
     *
     * @return string
     */
    protected function getDumpCommandLine(string $dbName, string $outputPath): string
    {
        return '';
    }
}
