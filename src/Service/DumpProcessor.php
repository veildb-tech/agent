<?php

declare(strict_types=1);

namespace App\Service;

use DbManager\CoreBundle\DBManagement\DBManagementFactory;
use DbManager\CoreBundle\Exception\NoSuchEngineException;
use DbManager\CoreBundle\Exception\ShellProcessorException;
use DbManager\CoreBundle\Service\DbDataManager;

/**
 * @deprecated
 */
class DumpProcessor
{
    /**
     * @param DBManagementFactory $dbManagementFactory
     */
    public function __construct(
        private readonly DBManagementFactory $dbManagementFactory
    ) {
    }

    /**
     * @param string $tempDatabase
     * @param string $backupPath
     * @param string $engine
     *
     * @return void
     * @throws ShellProcessorException
     * @throws NoSuchEngineException
     */
    public function dump(string $tempDatabase, string $backupPath = '', string $engine = 'mysql'): void
    {
        $dbManagement = $this->dbManagementFactory->create($engine);
        $dbManagement->dump(
            new DbDataManager([
                'name' => $tempDatabase,
                'backup_path' => $backupPath
            ])
        );
    }
}
