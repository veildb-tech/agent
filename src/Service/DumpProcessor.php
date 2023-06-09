<?php

declare(strict_types=1);

namespace App\Service;

use DbManager\CoreBundle\DumpProcessor\DumpProcessorFactory;
use DbManager\CoreBundle\Exception\NoSuchEngineException;
use DbManager\CoreBundle\Exception\ShellProcessorException;
use DbManager\CoreBundle\Service\DbDataManager;

class DumpProcessor
{
    /**
     * @param DumpProcessorFactory $dumpProcessorFactory
     */
    public function __construct(
        private readonly DumpProcessorFactory $dumpProcessorFactory
    ) {
    }

    /**
     * @param string $tempDatabase
     * @param string $backupPath
     *
     * @return void
     * @throws ShellProcessorException
     * @throws NoSuchEngineException
     */
    public function process(string $tempDatabase, string $backupPath = ''): void
    {
        $dumpProcessor = $this->dumpProcessorFactory->create();
        $dumpProcessor->execute(
            new DbDataManager([
                'name' => $tempDatabase,
                'backupPath' => $backupPath
            ])
        );
    }
}
