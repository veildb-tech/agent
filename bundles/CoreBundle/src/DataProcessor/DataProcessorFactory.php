<?php

declare(strict_types=1);

namespace DbManager\CoreBundle\DataProcessor;

use Illuminate\Database\Connection;

class DataProcessorFactory implements DataProcessorFactoryInterface
{
    /**
     * @inheritdoc
     */
    public function create(string $tableName, array $rule, Connection $connection): DataProcessorInterface
    {
        if (isset($rule['eav']) && $rule['eav'] === true) {
            return new EavDataProcessorService($tableName, $rule, $connection);
        }
        return new TableService($tableName, $rule, $connection);
    }
}