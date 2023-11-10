<?php

declare(strict_types=1);

namespace DbManager\MysqlBundle;

use DbManager\CoreBundle\Interfaces\DbDataManagerInterface;
use DbManager\CoreBundle\Interfaces\EngineInterface;
use DbManager\CoreBundle\Service\AbstractEngineProcessor;
use Exception;

/**
 * Mysql Processor instance
 */
class Processor extends AbstractEngineProcessor implements EngineInterface
{
    /**
     * Engine const
     */
    public const DRIVER_ENGINE = 'mysql';

    /**
     * @inheritdoc
     */
    public function process(DbDataManagerInterface $dbDataManager): void
    {
        $this->connection = $this->getDbConnection($dbDataManager->getName());
        $this->connection->statement('SET FOREIGN_KEY_CHECKS = 0');

        foreach ($dbDataManager->getIterableRules() as $table => $rule) {
            $this->dataProcessor = $this->dataProcessorFactory->create(
                $table,
                $rule,
                $dbDataManager->getPlatform(),
                $this->connection
            );

            $this->processTable($table, $rule);
        }

        $this->connection->statement('SET FOREIGN_KEY_CHECKS = 1');
    }

    /**
     * @inheritdoc
     */
    public function getDbStructure(DbDataManagerInterface $dbDataManager): array
    {
        $dbSchema   = [];
        $connection = $this->getDbConnection($dbDataManager->getName());

        $tables = new \ArrayIterator($connection->getDoctrineSchemaManager()->listTables());
        foreach ($tables as $table) {
            /** @var \Doctrine\DBAL\Schema\Table $table */
            /** @var \Doctrine\DBAL\Schema\Column $column */
            foreach ($table->getColumns() as $column) {
                $columnData = [
                    'type' => $column->getType()->getName(),
                    'length' => $column->getLength(),
                    'name' => $column->getName()
                ];
                $dbSchema[$table->getName()][$column->getName()] = $columnData;
            }
        }

        return [
            'db_schema'       => $dbSchema
        ];
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
        if (empty($rule['columns'])) {
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
     * @param string|null $column
     *
     * @return void
     * @throws Exception
     */
    protected function truncate(array $rule, ?string $column = null): void
    {
        if (!empty($rule['where'])) {
            $this->dataProcessor->delete($rule['where'], $column);

            return;
        }
        $this->dataProcessor->truncate();
    }

    /**
     * Update table
     *
     * @param array $rule
     * @param string $column
     *
     * @return void
     * @throws Exception
     */
    protected function update(array $rule, string $column): void
    {
        $this->dataProcessor->update($column, $rule['value'], $rule['where'] ?? null);
    }

    /**
     * @throws Exception
     */
    protected function fake(string $table, array $rule, string $column): void
    {
        if (!empty($rule['where'])) {
            $rows = $this->connection->select(
                sprintf('SELECT * FROM `%s` WHERE %s', $table, $rule['where'])
            );
        } else {
            $rows = $this->connection->select(sprintf('SELECT * FROM `%s`', $table));
        }

        $primaryKey = $this->getPrimaryKey($table);

        foreach ($rows as $row) {
            $method = $rule['value'] ?? $column;
            $this->dataProcessor->update(
                $column,
                sprintf("%s", $this->generateFake($method, $rule['options'] ?? [])),
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
