<?php

declare(strict_types=1);

namespace DbManager\CoreBundle\Service;

use App\Service\AppConfig;
use DbManager\CoreBundle\DataProcessor\DataProcessorFactoryInterface;
use DbManager\CoreBundle\DataProcessor\DataProcessorInterface;
use DbManager\CoreBundle\Interfaces\EngineInterface;
use DbManager\CoreBundle\Interfaces\ErrorInterface;
use DbManager\CoreBundle\Enums\ErrorSeverityEnum;
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
     * @var ErrorInterface[]
     */
    protected array $errors = [];

    /**
     * @param AppConfig $appConfig
     * @param DataProcessorFactoryInterface $dataProcessorFactory
     */
    public function __construct(
        protected readonly AppConfig $appConfig,
        protected readonly DataProcessorFactoryInterface $dataProcessorFactory
    ) {
    }

    /**
     * @return ErrorInterface[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @param string $message
     * @param int $severity
     * @return void
     */
    public function addError(string $message, int $severity = ErrorSeverityEnum::WARNING->value): void
    {
        $error = new DbDataManager\Error($message, $severity);
        $this->errors[] = $error;
    }

    /**
     * Get DB Connection
     *
     * @param string $dbName
     *
     * @return Connection
     * @throws Exception
     */
    protected function getDbConnection(string $dbName): Connection
    {
        $capsule = new Manager();
        $capsule->addConnection([
            'driver'    => static::DRIVER_ENGINE,
            'host'      => $this->appConfig->getDbEngineConfig('database_host', static::DRIVER_ENGINE),
            'port'      => $this->appConfig->getDbEngineConfig('database_port', static::DRIVER_ENGINE),
            'database'  => $dbName,
            'username'  => $this->appConfig->getDbEngineConfig('database_user', static::DRIVER_ENGINE),
            'password'  => $this->appConfig->getPassword(static::DRIVER_ENGINE),
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
     * @param string $method
     * @param array  $options
     *
     * @return string
     */
    protected function generateFake(string $method, array $options): string
    {
        return (string)$this->getFakerInstance()->{$method}(...$options);
        // TODO need to think about unique value. Currently it makes looping when there are a lot of records
        $value = $this->getFakerInstance()->{$method}(...$options);
        if (isset($this->generated[$method]) && in_array($value, $this->generated[$method])) {
            return $this->generateFake($method, $options);
        }

        $this->generated[$method][] = $value;

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

        if ($rule['method'] === 'update' || $rule['method'] === 'fake') {
            if (!$column) {
                throw new Exception('For method Update column is required');
            }

            /*if (!isset($rule['where'])) {
                throw new Exception('For method Update condition is required');
            }*/

            if (!key_exists('value', $rule)) {
                throw new Exception('For method Update value is required');
            }
        }
    }
}
