<?php

declare(strict_types=1);

namespace DbManager\MysqlBundle;

use DbManager\CoreBundle\Interfaces\DbDataManagerInterface;
use DbManager\CoreBundle\Interfaces\EngineInterface;
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
    public function execute(DbDataManagerInterface $dbDataManager): void
    {
        $this->connection = $this->getDbConnection($dbDataManager->getName());
        $this->connection->statement('SET FOREIGN_KEY_CHECKS = 0');

        foreach ($dbDataManager->getIterableRules() as $table => $rule) {
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
            $this->processMethod($table, $rule);

            return;
        }

        foreach ($rule['columns'] as $column => $rule) {
            $this->processMethod($table, $rule, $column);
        }
    }

    /**
     * Truncate table
     *
     * @param array $rule
     *
     * @return void
     */
    protected function truncate(array $rule): void
    {
        if (isset($rule['where'])) {
            $this->dataProcessor->delete($rule['where']);

            return;
        }
        $this->dataProcessor->truncate();
    }

    /**
     * Update table
     *
     * @param array  $rule
     * @param string $column
     *
     * @return void
     */
    protected function update(array $rule, string $column): void
    {
        if (isset($rule['where'])) {
            $this->dataProcessor->update($column, $rule['value'], $rule['where']);

            return;
        }
        $this->dataProcessor->update($column, $rule['value']);
    }

    protected function fake(string $table, array $rule, string $column): void
    {
        if (isset($rule['where'])) {
            $rows = $this->connection->select(
                sprintf('SELECT * FROM `%s` WHERE %s', $table, $rule['where'])
            );
        } else {
            $rows = $this->connection->select(sprintf('SELECT * FROM `%s`', $table));
        }

        $primaryKey = $this->getPrimaryKey($table);
        foreach ($rows as $row) {
            $this->dataProcessor->update(
                $column,
                sprintf("'%s'", $this->generateFake($column, [])),
                sprintf("`%s` = '%s'", $primaryKey, $row->{$primaryKey})
            );
        }
    }

    protected function getPrimaryKey(string $table): string
    {
        $sql = "SELECT column_name FROM information_schema.KEY_COLUMN_USAGE"
            ." WHERE CONSTRAINT_NAME='PRIMARY' AND TABLE_NAME='%s';";

        $key = $this->connection->selectOne(sprintf($sql, $table));

        return $key->column_name;
    }
}
