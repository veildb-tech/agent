<?php

declare(strict_types=1);

namespace DbManager\CoreBundle\Service;

use DbManager\CoreBundle\DataProcessor\DataProcessorFactoryInterface;
use DbManager\CoreBundle\DataProcessor\DataProcessorInterface;
use DbManager\CoreBundle\Interfaces\EngineInterface;
use Exception;
use Faker\Factory;
use Faker\Generator;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Connection;

/**
 * AbstractEngineProcessor Class
 */
abstract class AbstractEngineProcessor implements EngineInterface
{
    /**
     * @var array
     */
    protected array $generated = [];

    /**
     * @var null|Generator
     */
    protected ?Generator $faker = null;

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
            'port'      => env('DATABASE_PORT'),
            'database'  => $dbName,
            'username'  => env('DATABASE_USER'),
            'password'  => env('DATABASE_PASSWD'),
        ]);

        return $capsule->getConnection();
    }

    /**
     * Processing method
     *
     * @param string      $table
     * @param array       $rule
     * @param string|null $column
     *
     * @return void
     */
    protected function processMethod(string $table, array $rule, string $column = null): void
    {
        try {
            $this->validateRule($rule, $column);

            switch ($rule['method']) {
                case 'truncate':
                    $this->truncate($rule, $column);
                    break;
                case 'update':
                    $this->update($rule, $column);
                    break;
                case 'fake':
                    $this->fake($table, $rule, $column);
                    break;
            }
        } catch (Exception $e) {
            return;
        }
    }

    /**
     * Generate fake data
     *
     * @param string $column
     * @param array  $options
     *
     * @return string
     */
    protected function generateFake(string $column, array $options): string
    {
        $value = $this->getFakerInstance()->{$column}(...$options);
        if (isset($this->generated[$column]) && in_array($value, $this->generated[$column])) {
            return $this->generateFake($column, $options);
        }

        $this->generated[$column][] = $value;

        return $value;
    }

    /**
     * Get Faker generator
     *
     * @return Generator
     */
    protected function getFakerInstance(): Generator
    {
        if ($this->faker) {
            return $this->faker;
        }

        $this->faker = Factory::create();

        return $this->faker;
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
