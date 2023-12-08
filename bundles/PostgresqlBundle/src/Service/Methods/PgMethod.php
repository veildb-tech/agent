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
     * @return array
     */
    protected function askDatabaseConfig(InputOutput $inputOutput): array
    {
        $config = [];

        $config['db_host'] = $inputOutput->ask('Database Host', 'localhost', self::validateRequired(...));
        $config['db_user'] = $inputOutput->ask('Database User:', 'root', self::validateRequired(...));
        $config['db_password'] = $inputOutput->askHidden('Password');
        $config['db_name'] = $inputOutput->ask('Database name:', null, self::validateRequired(...));
        $config['db_port'] = $inputOutput->ask('Database Port: ', '5432', self::validateRequired(...));

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
            $config['db_host'],
            $config['db_port'],
            $config['db_name']
        );
    }
}
