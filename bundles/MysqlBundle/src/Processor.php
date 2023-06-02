<?php

declare(strict_types=1);

namespace DbManager\MysqlBundle;

use DbManager\CoreBundle\Interfaces\EngineInterface;
use DbManager\CoreBundle\Interfaces\RuleManagerInterface;
use DbManager\CoreBundle\Interfaces\TempDatabaseInterface;
use DbManager\CoreBundle\Service\AbstractEngineProcessor;

/**
 * Mysql Processor instance
 */
final class Processor extends AbstractEngineProcessor implements EngineInterface
{
    /**
     * Engine const
     */
    public const DRIVER_ENGINE = 'mysql';

    /**
     * @inheritdoc
     */
    public function execute(RuleManagerInterface $rules, TempDatabaseInterface $tempDatabase): void
    {
        $this->connection = $this->getDbConnection($tempDatabase->getName());
        $this->connection->statement('SET FOREIGN_KEY_CHECKS = 0');

        foreach ($rules->getIterableRules() as $table => $rule) {
            $this->processTable($table, $rule);
        }

        $this->connection->statement('SET FOREIGN_KEY_CHECKS = 1');
    }

    /**
     * Process table according to passed rules
     *
     * @param string $table
     * @param array  $rule
     *
     * @return void
     */
    protected function processTable(string $table, array $rule): void
    {
        $this->dataProcessor = $this->dataProcessorFactory->create($table, $this->connection);
        if (!isset($rule['columns'])) {
            $this->processMethod($rule);

            return;
        }

        foreach ($rule['columns'] as $column => $rule) {
            $this->processMethod($rule, $column);
        }
    }
}
