<?php

declare(strict_types=1);

namespace DbManager\PostgresqlBundle;

use DbManager\CoreBundle\Interfaces\DbDataManagerInterface;
use DbManager\CoreBundle\Interfaces\EngineInterface;
use DbManager\CoreBundle\Service\AbstractEngineProcessor;
use DbManager\PostgresqlBundle\Service\Engine\Postgresql as PostgresqlEngine;
use Exception;

/**
 * Mysql Processor instance
 */
class Processor extends AbstractEngineProcessor implements EngineInterface
{
    /**
     * Engine const
     */
    public const DRIVER_ENGINE = PostgresqlEngine::ENGINE_CODE;

    /**
     * @inheritdoc
     */
    public function process(DbDataManagerInterface $dbDataManager): void
    {
        $this->connection = $this->getDbConnection($dbDataManager);

        foreach ($dbDataManager->getIterableRules() as $table => $rule) {
            $this->dataProcessor = $this->dataProcessorFactory->create(
                $table,
                $rule,
                $dbDataManager->getPlatform(),
                $this->connection
            );

            $this->processTable($table, $rule);
        }
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
                sprintf('SELECT %s FROM %s WHERE %s', $primaryKey, $this->currentTable, $rule['where'])
            );
        } else {
            $rows = $this->connection->select(sprintf('SELECT %s FROM %s', $primaryKey, $this->currentTable));
        }

        foreach ($rows as $row) {
            $this->dataProcessor->update(
                $column,
                $this->interpolateValue($value, $columnType),
                sprintf("%s = %s", $primaryKey, $row->{$primaryKey})
            );
        }
    }

    /**
     * @throws Exception
     */
    protected function fake(string $table, array $rule, string $column): void
    {
        $this->logDebug("Start processing fake: {$table}::{$column}");
        $primaryKey = $this->getPrimaryKey($table);

        if ($primaryKey === null) {
            // TODO: Improve updating without primary key
            $this->addError(sprintf("Can't allocate primary key for table \"%s\". Skipping this table", $table));
            $this->logDebug("Finish processing fake: {$table}::{$column}");
            return;
        }

        $columnType = $this->getColumnType($table, $column);

        if (!empty($rule['where'])) {
            $rows = $this->connection->select(
                sprintf('SELECT %s, %s FROM %s WHERE %s', $primaryKey, $column, $table, $rule['where'])
            );
        } else {
            $rows = $this->connection->select(sprintf('SELECT %s, %s FROM %s', $primaryKey, $column, $table));
        }

        $method  = $rule['value'] ?? $column;
        $options = $this->getRuleOptions($rule);

        if ($this->isArrayType((string)$columnType)) {
            $elementType = substr((string)$columnType, 1); // strip leading '_'
            $isNumeric   = in_array($elementType, ['int2', 'int4', 'int8', 'float4', 'float8', 'numeric']);
            $processedData = [];
            foreach ($rows as $row) {
                $elements    = $this->parsePostgresArray((string)$row->{$column});
                $newElements = array_map(
                    fn($element) => $this->faker->generateFake($method, $options, null, $elementType),
                    $elements
                );
                $processedData[] = [
                    $primaryKey => $row->{$primaryKey},
                    $column     => $this->serializePostgresArray($newElements, $isNumeric),
                ];
            }
        } else {
            $columnMaxLength = $this->getColumnMaxLength($table, $column);
            $fakeCollection  = $this->faker->generateFakeCollection(
                $method,
                $options,
                count($rows),
                $this->isUniqueMethod($method),
                $columnMaxLength,
                $columnType
            );
            $processedData = [];
            foreach ($rows as $row) {
                $processedData[] = [
                    $primaryKey => $row->{$primaryKey},
                    $column     => array_shift($fakeCollection),
                ];
            }
        }

        if (count($processedData) > 1000) {
            $this->connection->beginTransaction();
            foreach (array_chunk($processedData, 1000) as $chunk) {
                foreach ($chunk as $row) {
                    $this->dataProcessor->update(
                        $column,
                        sprintf("%s", $row[$column]),
                        sprintf("%s = %s", $primaryKey, $row[$primaryKey])
                    );
                }
            }
            $this->connection->commit();
        } else {
            foreach ($processedData as $row) {
                $this->dataProcessor->update(
                    $column,
                    sprintf("%s", $row[$column]),
                    sprintf("%s = %s", $primaryKey, $row[$primaryKey])
                );
            }
        }

        $this->logDebug("Finish processing fake: {$table}::{$column}");
    }

    /**
     * Retrieve primary key
     * @see https://wiki.postgresql.org/wiki/Retrieve_primary_key_columns
     * @param string $table
     * @return string|null
     */
    protected function getPrimaryKey(string $table): ?string
    {
        $sql = "SELECT a.attname, format_type(a.atttypid, a.atttypmod) AS data_type
            FROM   pg_index i
            JOIN   pg_attribute a ON a.attrelid = i.indrelid AND a.attnum = ANY(i.indkey)
            WHERE  i.indrelid = '%s'::regclass AND i.indisprimary;";

        $key = $this->connection->selectOne(sprintf($sql, $table));

        return $key?->attname;
    }

    /**
     * @param string $table
     * @param string $column
     * @return int|null
     */
    protected function getColumnMaxLength(string $table, string $column): ?int
    {
        $sql = "SELECT character_maximum_length
                FROM information_schema.COLUMNS
                WHERE COLUMN_NAME='%s' AND TABLE_NAME='%s';";

        $key = $this->connection->selectOne(sprintf($sql, $column, $table));

        return $key?->character_maximum_length;
    }

    /**
     * Returns the PostgreSQL internal type name (udt_name), e.g. "text", "_text", "int4", "timestamp".
     *
     * @param string $table
     * @param string $column
     * @return string|null
     */
    protected function getColumnType(string $table, string $column): ?string
    {
        $sql = "SELECT udt_name
                FROM information_schema.columns
                WHERE column_name = '%s' AND table_name = '%s';";

        $key = $this->connection->selectOne(sprintf($sql, $column, $table));

        return $key?->udt_name;
    }

    /**
     * Returns true for PostgreSQL array types (udt_name starts with '_').
     */
    private function isArrayType(string $columnType): bool
    {
        return str_starts_with($columnType, '_');
    }

    /**
     * Parses a PostgreSQL array literal (e.g. {"foo","bar"} or {1,2,3}) into a PHP array.
     *
     * @return string[]
     */
    private function parsePostgresArray(string $value): array
    {
        $inner = substr($value, 1, -1); // strip outer { }
        if ($inner === '') {
            return [];
        }
        return str_getcsv($inner, ',', '"');
    }

    /**
     * Serialises a PHP array back to a PostgreSQL array literal.
     * String values are quoted; numeric values are left unquoted.
     *
     * @param string[] $values
     * @param bool     $numeric
     * @return string
     */
    private function serializePostgresArray(array $values, bool $numeric = false): string
    {
        $parts = array_map(static function ($v) use ($numeric): string {
            if ($v === null) {
                return 'NULL';
            }
            if ($numeric) {
                return (string)$v;
            }
            return '"' . str_replace('"', '\\"', (string)$v) . '"';
        }, $values);

        return '{' . implode(',', $parts) . '}';
    }
}
