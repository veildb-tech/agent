<?php

declare(strict_types=1);

namespace DbManager\MysqlBundle;

use DbManager\CoreBundle\DataProcessor\DataProcessorFactoryInterface;
use DbManager\CoreBundle\Interfaces\EngineInterface;
use DbManager\CoreBundle\Interfaces\RuleManagerInterface;
use DbManager\CoreBundle\Interfaces\TempDatabaseInterface;
use DbManager\CoreBundle\Service\AbstractEngineProcessor;

final class Processor extends AbstractEngineProcessor implements EngineInterface
{
    /**
     * Engine const
     */
    public const DRIVER_ENGINE = 'mysql';

    /**
     * @param DataProcessorFactoryInterface $dataProcessorFactory
     */
    public function __construct(private readonly DataProcessorFactoryInterface $dataProcessorFactory)
    {
    }

    public function execute(RuleManagerInterface $rules, TempDatabaseInterface $tempDatabase): void
    {
        $this->connection = $this->getDbConnection($tempDatabase->getName());
        $this->connection->statement('SET FOREIGN_KEY_CHECKS=0');

        // Steps:
        // 1. Get all tables from rules
        // 2. On each table:
        // 2.1. Get method of processing
        // 2.1.1 Check is method has some specific rules ( has where, or it fake )
        // 2.1.1.1 Form rules into query
        // 2..2. Execute query

        foreach ($rules->getIterableRules() as $table => $rules) {
            $this->processRule($table, $rules);
        }

        $this->connection->statement('SET FOREIGN_KEY_CHECKS=1');
    }

    protected function processRule(string $table, array $rule): void
    {
        $dataProcessor = $this->dataProcessorFactory->create($table, $this->connection);

        if (isset($rule['method'])) {
            switch ($rule['method']) {
                case 'truncate':
                    $this->truncate($rule, $dataProcessor);
                    break;
                case 'fake':
                    break;
            }
        }

        if (isset($rule['columns'])) {
            foreach ($rule['columns'] as $column => $rule) {

            }
        }
    }

    protected function truncate(array $rules, $dataProcessor)
    {
        if ($rules['where']) {
            $dataProcessor->delete($rules['where']);

            return;
        }
        $dataProcessor->truncate();
    }

    protected function fake()
    {

    }

    protected function validateWhere(string $where): void
    {

    }
}
