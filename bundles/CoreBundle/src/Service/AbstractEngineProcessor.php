<?php

declare(strict_types=1);

namespace DbManager\CoreBundle\Service;

use DbManager\CoreBundle\DataProcessor\DataProcessorFactoryInterface;
use DbManager\CoreBundle\DataProcessor\DataProcessorInterface;
use DbManager\CoreBundle\Interfaces\EngineInterface;
use Exception;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Connection;

/**
 * AbstractEngineProcessor Class
 */
abstract class AbstractEngineProcessor implements EngineInterface
{
    /**
     * @var Connection
     */
    protected Connection $connection;

    /**
     * @var DataProcessorInterface
     */
    protected DataProcessorInterface $dataProcessor;

    /**
     * @param DataProcessorFactoryInterface $dataProcessorFactory
     */
    public function __construct(protected readonly DataProcessorFactoryInterface $dataProcessorFactory)
    {
    }

    /**
     * Get DB Connection
     *
     * @param string $dbName
     *
     * @return Connection
     */
    protected function getDbConnection(string $dbName): Connection
    {
        $capsule = new Manager();
        $capsule->addConnection([
            'driver'    => static::DRIVER_ENGINE,
            'host'      => env('DATABASE_HOST'),
            'database'  => $dbName,
            'username'  => env('DATABASE_USER'),
            'password'  => env('DATABASE_PASSWD'),
        ]);

        return $capsule->getConnection();
    }

    /**
     * Processing method
     *
     * @param array       $rule
     * @param string|null $column
     *
     * @return void
     */
    protected function processMethod(array $rule, string $column = null): void
    {
        try {
            $this->validateRule($rule, $column);

            switch ($rule['method']) {
                case 'truncate':
                    $this->truncate($rule);
                    break;
                case 'update':
                    $this->update($rule, $column);
                    break;
                case 'fake':
                    $this->fake($rule);
                    break;
            }
        } catch (Exception $e) {
            return;
        }
    }

    protected function truncate(array $rule): void
    {
        if (isset($rule['where'])) {
            $this->dataProcessor->delete($rule['where']);

            return;
        }
        $this->dataProcessor->truncate();
    }

    protected function update(array $rule, string $column): void
    {
        if (isset($rule['where'])) {
            $this->dataProcessor->update($column, $rule['value'], $rule['where']);

            return;
        }
        $this->dataProcessor->update($column, $rule['value']);
    }

    protected function fake(array $rule): void
    {

    }

    /**
     * Validate passed rule
     *
     * @param array       $rule
     * @param string|null $column
     *
     * @return void
     *
     * @throws Exception
     */
    protected function validateRule(array $rule, ?string $column = null): void
    {
        if (!isset($rule['method'])) {
            throw new Exception('The method is required');
        }

        if ($rule['method'] === 'update') {
            if (!$column) {
                throw new Exception('For method Update column is required');
            }

            if (!isset($rule['where'])) {
                throw new Exception('For method Update condition is required');
            }

            if (!key_exists('value', $rule)) {
                throw new Exception('For method Update value is required');
            }
        }
    }
}
