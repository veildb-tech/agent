<?php

declare(strict_types=1);

namespace DbManager\CoreBundle\DataProcessor;

use Illuminate\Database\Connection;

class DataProcessorFactory implements DataProcessorFactoryInterface
{
    /**
     * @inheritdoc
     */
    public function create(string $tableName, Connection $connection): DataProcessorInterface
    {
        return new TableService($tableName, $connection);
    }
}