<?php

declare(strict_types=1);

namespace DbManager\MysqlBundle;

use DbManager\CoreBundle\Interfaces\DbDataManagerInterface;
use DbManager\CoreBundle\Interfaces\EngineInterface;
use DbManager\CoreBundle\Service\AbstractEngineProcessor;
use DbManager\MysqlBundle\Service\Engine\Mysql;
use Exception;
use Throwable;

/**
 * Mysql Processor instance
 */
class Processor extends AbstractEngineProcessor implements EngineInterface
{
    /**
     * Engine const
     */
    public const DRIVER_ENGINE = Mysql::ENGINE_CODE;

    /**
     * @inheritdoc
     */
    public function process(DbDataManagerInterface $dbDataManager): void
    {
        $this->connection = $this->getDbConnection($dbDataManager);
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
        $value = $rule['value'];

        if (!str_contains($value, '{faker.')) {
            $this->dataProcessor->update($column, $value, $rule['where'] ?? null);
            return;
        }

        $columnType = $this->getColumnType($this->currentTable, $column);
        $primaryKey = $this->getPrimaryKey($this->currentTable);

        if ($primaryKey === null) {
            $this->addError(sprintf("Can't allocate primary key for table \"%s\". Skipping this table", $this->currentTable));
            return;
        }

        if (!empty($rule['where'])) {
            $rows = $this->connection->select(
                sprintf('SELECT %s FROM `%s` WHERE %s', $primaryKey, $this->currentTable, $rule['where'])
            );
        } else {
            $rows = $this->connection->select(sprintf('SELECT %s FROM `%s`', $primaryKey, $this->currentTable));
        }

        foreach ($rows as $row) {
            $this->dataProcessor->update(
                $column,
                $this->interpolateValue($value, $columnType),
                sprintf("`%s` = '%s'", $primaryKey, $row->{$primaryKey})
            );
        }
    }

    /**
     * @throws Exception
     * @throws Throwable
     */
    protected function fake(string $table, array $rule, string $column): void
    {
        $this->logDebug("Start processing fake: {$table}::{$column}");
        $primaryKey = $this->getPrimaryKey($table);

        if ($primaryKey === null) {
            // TODO: Improve updating without primary key
            $this->addError(sprintf("Can't allocate primary key for table \"%s\". Skipping this table", $table));
        } else {
            $columnMaxLength = $this->getColumnMaxLength($table, $column);
            $columnType      = $this->getColumnType($table, $column);
            if (!empty($rule['where'])) {
                $rows = $this->connection->select(
                    sprintf('SELECT %s, %s FROM `%s` WHERE %s', $primaryKey, $column, $table, $rule['where'])
                );
            } else {
                $rows = $this->connection->select(sprintf('SELECT %s, %s FROM `%s`', $primaryKey, $column, $table));
            }

            $processedData = [];
            $method = $rule['value'] ?? $column;
            $fakeCollection = $this->faker->generateFakeCollection(
                $method,
                $this->getRuleOptions($rule),
                count($rows),
                $this->isUniqueMethod($method),
                $columnMaxLength,
                $columnType
            );

            foreach ($rows as $row) {
                $fakeValue = array_shift($fakeCollection);
                $processedData[] = [
                    $primaryKey => $row->{$primaryKey},
                    $column => $fakeValue
                ];
            }

            if (count($processedData) > 1000) {
                $this->connection->beginTransaction();
                foreach (array_chunk($processedData, 1000) as $chunk) {
                    foreach ($chunk as $row) {
                        $this->dataProcessor->update(
                            $column,
                            sprintf("%s", $row[$column]),
                            sprintf("`%s` = '%s'", $primaryKey, $row[$primaryKey])
                        );
                    }
                }
                $this->connection->commit();
            } else {
                foreach ($processedData as $row) {
                    $this->dataProcessor->update(
                        $column,
                        sprintf("%s", $row[$column]),
                        sprintf("`%s` = '%s'", $primaryKey, $row[$primaryKey])
                    );
                }
            }
        }
        $this->logDebug("Finish processing fake: {$table}::{$column}");
    }

    /**
     * Retrieve primary key for table
     *
     * @param string $table
     * @return string|null
     */
    protected function getPrimaryKey(string $table): ?string
    {
        /**
         * Explanation of "column_name as column_name". For some versions COLUMN_NAME could be uppercase
         * or lowercase. To avoid issue add "as" construction
         */
        $sql = "SELECT column_name as column_name FROM information_schema.KEY_COLUMN_USAGE"
            ." WHERE CONSTRAINT_NAME='PRIMARY' AND TABLE_NAME='%s';";

        $key = $this->connection->selectOne(sprintf($sql, $table));

        return $key?->column_name;
    }

    /**
     * @param string $table
     * @param string $column
     * @return int|null
     */
    protected function getColumnMaxLength(string $table, string $column): ?int
    {
        $sql = "SELECT CHARACTER_MAXIMUM_LENGTH as character_maximum_length
                FROM information_schema.COLUMNS
                WHERE COLUMN_NAME='%s' AND TABLE_NAME='%s';";

        $key = $this->connection->selectOne(sprintf($sql, $column, $table));

        return $key?->character_maximum_length;
    }

    /**
     * @param string $table
     * @param string $column
     * @return string|null
     */
    protected function getColumnType(string $table, string $column): ?string
    {
        $sql = "SELECT DATA_TYPE as data_type
                FROM information_schema.COLUMNS
                WHERE COLUMN_NAME='%s' AND TABLE_NAME='%s';";

        $key = $this->connection->selectOne(sprintf($sql, $column, $table));

        return $key?->data_type;
    }
}
