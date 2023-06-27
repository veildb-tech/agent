<?php

declare(strict_types=1);

namespace DbManager\CoreBundle\DataProcessor;

use Illuminate\Database\Connection;

interface DataProcessorFactoryInterface
{
    /**
     * Create Data Processor instance
     *
     * @param string     $tableName
     * @param array      $rule
     * @param Connection $connection
     *
     * @return DataProcessorInterface
     */
    public function create(string $tableName, array $rule, Connection $connection): DataProcessorInterface;
}
