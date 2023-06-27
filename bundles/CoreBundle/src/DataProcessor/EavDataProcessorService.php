<?php

declare(strict_types=1);

namespace DbManager\CoreBundle\DataProcessor;

use Exception;
use Illuminate\Database\Connection;
use stdClass;

final class EavDataProcessorService implements DataProcessorInterface
{
    /**
     * @var string
     */
    private string $tableName;

    /**
     * @var array
     */
    private array $rule;

    /**
     * @var array
     */
    private array $attributes = [];

    /**
     * @var Connection
     */
    private Connection $connection;

    /**
     * @param string     $tableName
     * @param array      $rule
     * @param Connection $connection
     */
    public function __construct(string $tableName, array $rule, Connection $connection)
    {
        $this->tableName = $tableName;
        $this->rule = $rule;
        $this->connection = $connection;
    }

    /**
     * @inheritdoc
     */
    public function delete(string $condition, ?string $column = null): void
    {
        $this->update($column, 'NULL', $condition);
    }

    /**
     * @inheritdoc
     */
    public function truncate(?string $column = null): void
    {
        $this->update($column, 'NULL');
    }

    /**
     * @inheritdoc
     */
    public function update(string $field, string $value, ?string $condition = null): void
    {
        $attributeData = $this->getAttributeData($field);

        if ($attributeData['backend_type'] === 'static' || $attributeData['source_model'] !== null) {
            return;
        }

        $query = $this->connection->table(
            $attributeData['attribute_table']
        );

        if ($condition) {
            $rowId = $this->getEntityRowId($condition);

            $query->where('row_id', '=', $rowId);
        }

        $query->where(
            'attribute_id',
            '=',
            $attributeData['attribute_id']
        )->update(
            [
                'value' => $this->connection->raw($value),
            ]
        );
    }

    /**
     * TODO: Test case when result = 0
     *
     * @param string $condition
     *
     * @return int
     * @throws Exception
     */
    private function getEntityRowId(string $condition): int
    {
        $entity = $this->connection->query()
            ->select(
                ['entity_id', 'attribute_set_id', 'row_id']
            )->from(
                $this->tableName
            )->whereRaw(
                $condition
            )->first(
                ['attribute_set_id', 'row_id']
            );

        if (!$entity->row_id) {
            throw new Exception('The Entity with ID was not found.');
        }
        return $entity->row_id;
    }

    /**
     * Get Attribute data
     *
     * @throws Exception
     */
    private function getAttributeData(string $field): array
    {
        if (isset($this->attributes[$field])) {
            return $this->attributes[$field];
        }

        if (!$entity = $this->getEntity()) {
            throw new Exception('Entity not found');
        }

        $attributes = $this->connection->query()
            ->select(
                ['attribute_id', 'is_unique', 'backend_type', 'attribute_code']
            )->from(
                'eav_attribute'
            )->where(
                'entity_type_id',
                '=',
                $entity->entity_type_id
            )->whereIn(
                'attribute_code',
                $this->getColumns()
            )->get(
                ['attribute_id', 'is_unique', 'backend_type', 'attribute_code']
            );

        $baseTable = $entity->value_table_prefix ?: $this->tableName;

        foreach ($attributes as $attribute) {
            $this->attributes[$attribute->attribute_code] = [
                'attribute_table' => $baseTable . '_' . $attribute->backend_type,
                'attribute_id'    => $attribute->attribute_id,
                'backend_type'    => $attribute->backend_type ?? null,
                'source_model'    => $attribute->source_model ?? null,
            ];
        }

        return $this->attributes[$field];
    }

    /**
     * Get Entity
     *
     * @return stdClass|null
     */
    private function getEntity(): ?stdClass
    {
        return $this->connection->query()
            ->select(
                ['entity_table', 'value_table_prefix', 'entity_type_id']
            )->from(
                'eav_entity_type'
            )->where(
                'entity_table',
                '=',
                $this->tableName
            )->first(
                ['entity_type_id', 'value_table_prefix']
            );
    }

    /**
     * Get columns list
     *
     * @return array
     */
    private function getColumns(): array
    {
        return array_keys($this->rule['columns']);
    }
}
