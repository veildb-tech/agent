<?php

declare(strict_types=1);

namespace DbManager\CoreBundle\Service;

use App\Service\AppConfig;
use Symfony\Component\HttpKernel\Log\Logger;
use DbManager\CoreBundle\DataProcessor\DataProcessorFactoryInterface;
use DbManager\CoreBundle\Interfaces\DbDataManagerInterface;
use DbManager\CoreBundle\DataProcessor\DataProcessorInterface;
use DbManager\CoreBundle\Interfaces\EngineInterface;
use DbManager\CoreBundle\Interfaces\ErrorInterface;
use DbManager\CoreBundle\Enums\ErrorSeverityEnum;
use DbManager\CoreBundle\Service\Processor\Faker;
use Exception;
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

    private Logger $logger;

    /**
     * @param AppConfig $appConfig
     * @param DataProcessorFactoryInterface $dataProcessorFactory
     * @param Faker $faker
     */
    public function __construct(
        protected readonly AppConfig $appConfig,
        protected readonly DataProcessorFactoryInterface $dataProcessorFactory,
        protected readonly Faker $faker
    ) {
        $this->logger = new Logger($this->appConfig->getLogLevel()->value);
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
     * @inheritdoc
     */
    public function getDbStructure(DbDataManagerInterface $dbDataManager): array
    {
        $dbSchema = [];
        $connection = $this->getDbConnection($dbDataManager);

        // Warning: don't use getDoctrineSchemaManager cause it creates issues with symfony / doctrine databases
        $schemaBuilder = $connection->getSchemaBuilder();

        $tables = $schemaBuilder->getTables();
        foreach ($tables as $table) {
            $fkByColumn = [];
            foreach ($schemaBuilder->getForeignKeys($table['name']) as $fk) {
                foreach ($fk['columns'] as $i => $col) {
                    $fkByColumn[$col] = [
                        'foreign_table'  => $fk['foreign_table'],
                        'foreign_column' => $fk['foreign_columns'][$i] ?? $fk['foreign_columns'][0],
                    ];
                }
            }

            $primaryColumns = [];
            foreach ($schemaBuilder->getIndexes($table['name']) as $index) {
                if ($index['primary']) {
                    $primaryColumns = array_flip($index['columns']);
                    break;
                }
            }

            $columns = $schemaBuilder->getColumns($table['name']);
            foreach ($columns as $column) {
                $columnData = [
                    'type' => $column['type_name'],
                    'name' => $column['name']
                ];
                if (isset($primaryColumns[$column['name']])) {
                    $columnData['primary_key'] = true;
                }
                if (isset($fkByColumn[$column['name']])) {
                    $columnData['foreign_key'] = $fkByColumn[$column['name']];
                }
                $dbSchema[$table['name']][$column['name']] = $columnData;
            }
        }

        return [
            'db_schema' => $dbSchema
        ];
    }

    /**
     * Get DB Connection
     *
     * @param DbDataManagerInterface $dbDataManager
     * @return Connection
     * @throws Exception
     */
    protected function getDbConnection(DbDataManagerInterface $dbDataManager): Connection
    {
        $capsule = new Manager();
        $driverEngine = $this->getDriverEngine($dbDataManager);
        $capsule->addConnection([
            'driver'    => ($driverEngine == 'mariadb') ? 'mysql' : $driverEngine,
            'host'      => $this->appConfig->getDbEngineConfig('database_host', $driverEngine),
            'port'      => $this->appConfig->getDbEngineConfig('database_port', $driverEngine),
            'database'  => $dbDataManager->getName(),
            'username'  => $this->appConfig->getDbEngineConfig('database_user', $driverEngine),
            'password'  => $this->appConfig->getPassword($driverEngine),
        ]);

        return $capsule->getConnection();
    }

    protected function getDriverEngine(DbDataManagerInterface $dbDataManager): string
    {
        return static::DRIVER_ENGINE;
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
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
            $this->addError($exception->getMessage(), ErrorSeverityEnum::ERROR->value);
        }
    }

    /**
     * @param array $rule
     * @return array
     */
    protected function getRuleOptions(array $rule): array
    {
        return $rule['options'] ?? [];
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

    /**
     * TODO: remove this method when "unique" option will be added on service side
     *
     * @param string $fakeMethod
     * @return bool
     */
    protected function isUniqueMethod(string $fakeMethod): bool
    {
        return in_array($fakeMethod, ['email', 'safeEmail']);
    }

    /**
     * @param string $message
     * @return void
     * @throws Exception
     */
    protected function logDebug(string $message): void
    {
        $this->logger->log('debug', $message);
    }
}
