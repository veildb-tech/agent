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
     * @param Connection $connection
     *
     * @return DataProcessorInterface
     */
    public function create(string $tableName, Connection $connection): DataProcessorInterface;
}