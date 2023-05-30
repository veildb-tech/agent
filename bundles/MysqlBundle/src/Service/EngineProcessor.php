<?php

declare(strict_types=1);

namespace DbManager\MysqlBundle\Service;

use DbManager\CoreBundle\Interfaces\EngineInterface;
use DbManager\CoreBundle\Interfaces\RuleManagerInterface;
use DbManager\CoreBundle\Interfaces\TempDatabaseInterface;
use DbManager\CoreBundle\Service\AbstractEngineProcessor;

final class EngineProcessor extends AbstractEngineProcessor implements EngineInterface
{
    /**
     * Engine const
     */
    public const DRIVER_ENGINE = 'mysql';

    public function execute(RuleManagerInterface $rules, TempDatabaseInterface $tempDatabase): void
    {
        $connection = $this->getDbConnection($tempDatabase->getName());

        foreach ($rules->getTables() as $table => $rules) {
            if ($rules['method']) {
                switch ($rules['method']) {
                    case 'truncate':
                        if ($rules['where']) {
                            $connection->table($table)->where($rules['where'])->truncate();
                        }
                        $connection->table($table)->truncate();
                        break;
                    case 'fake':
                        break;
                }
            }
        }
    }
}
