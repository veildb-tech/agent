<?php

declare(strict_types=1);

namespace DbManager\PostgresqlBundle\Service\Methods;

use App\Service\InputOutput;
use App\Service\Methods\AbstractMethod;
use DbManager\PostgresqlBundle\Service\Engine\Postgresql as PostgresqlEngine;

abstract class PgMethod extends AbstractMethod
{
    /**
     * @param string $engine
     * @return bool
     */
    public function support(string $engine): bool
    {
        return $engine === PostgresqlEngine::ENGINE_CODE;
    }

    /**
     * @param InputOutput $inputOutput
     * @param array $config
     *
     * @return array
     */
    protected function askDatabaseConfig(InputOutput $inputOutput, array $config = []): array
    {
        $config['db_host'] = $inputOutput->ask(
            'Database Host',
            $config['db_host'] ?? 'localhost',
            self::validateRequired(...)
        );
        $config['db_user'] = $inputOutput->ask(
            'Database User',
            $config['db_user'] ?? 'root',
            self::validateRequired(...)
        );
        $config['db_password'] = $inputOutput->askHidden('Password');
        $config['db_name'] = $inputOutput->ask(
            'Database Name',
            $config['db_name'] ?? null,
            self::validateRequired(...)
        );
        $config['db_port'] = $inputOutput->ask(
            'Database Port ',
            $config['db_port'] ?? '5432',
            self::validateRequired(...)
        );

        return $config;
    }

    /**
     * @param array $config
     * @return string
     */
    protected function getPgsqlUrl(array $config): string
    {
        $password = $config['db_password'] ? ':' . $config['db_password'] : '';
        return sprintf(
            "postgresql://%s%s@%s:%s/%s",
            $config['db_user'],
            $password,
            $this->getConnectionHost($config),
            $config['db_port'],
            $config['db_name']
        );
    }
}
