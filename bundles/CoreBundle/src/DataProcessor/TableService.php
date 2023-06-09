<?php

declare(strict_types=1);

namespace DbManager\CoreBundle\DataProcessor;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;

final class TableService implements DataProcessorInterface
{
    /**
     * @var string
     */
    private string $tableName;

    /**
     * @var Connection
     */
    private Connection $connection;

    /**
     * @param string     $tableName
     * @param Connection $connection
     */
    public function __construct(string $tableName, Connection $connection)
    {
        $this->tableName = $tableName;
        $this->connection = $connection;
    }

    /**
     * @inheritdoc
     */
    public function truncate(): void
    {
        $this->query(true)->truncate();
    }

    /**
     * @inheritdoc
     */
    public function delete(string $condition): void
    {
        $this->queryWithCondition($condition, true)->delete();
    }

    /**
     * @inheritdoc
     */
    public function update(string $field, string $value, ?string $condition = null): void
    {
        if ($condition) {
            $this->queryWithCondition($condition, true)->update(
                [
                    $field => $this->connection->raw($value),
                ]
            );

            return;
        }
        $this->query(true)->update(
            [
                $field => $this->connection->raw($value),
            ]
        );
    }

    /**
     * Create a query with base select from this table
     *
     * @param bool $withoutAlias do not use alias for a main table
     *
     * @return Builder
     */
    public function query(bool $withoutAlias = false): Builder
    {
        $tableExpression = $withoutAlias ? $this->tableName : sprintf("%s as main", $this->tableName);

        return $this->connection->query()->from($tableExpression);
    }

    /**
     * Returns base select query with attached condition
     *
     * @param string $condition
     * @param bool   $withoutAlias do not use alias for a main table
     *
     * @return Builder
     */
    public function queryWithCondition(string $condition, bool $withoutAlias = false): Builder
    {
        $query = $this->query($withoutAlias);

        if ($condition) {
            $query->whereRaw($condition);
        }

        return $query;
    }
}
