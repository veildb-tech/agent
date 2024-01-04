<?php

declare(strict_types=1);

namespace DbManager\MysqlBundle\Service\Methods;

use App\Service\InputOutput;
use App\Service\Methods\AbstractMethod;
use DbManager\MysqlBundle\Service\Engine\Mysql as MysqlEngine;
use Exception;

class Mysqldump extends AbstractMethod
{
    /**
     * @param array $dbConfig
     * @param string $dbUuid
     * @param string|null $filename
     *
     * @return string
     * @throws Exception
     */
    public function execute(array $dbConfig, string $dbUuid, ?string $filename = null): string
    {
        $destFile = $this->getDestinationFile($dbUuid, $filename);
        $dbPassword = !empty($dbConfig['db_password']) ? sprintf('-p%s', $dbConfig['db_password']) : '';
        $this->shellProcess->run(sprintf(
            "mysqldump -u %s %s -h%s -P%s %s > %s",
            $dbConfig['db_user'],
            $dbPassword,
            $this->getConnectionHost($dbConfig),
            $dbConfig['db_port'],
            $dbConfig['db_name'],
            $destFile
        ));

        return $destFile;
    }

    /**
     * @param array $config
     *
     * @return bool
     * @throws Exception
     */
    public function validate(array $config): bool
    {
        $dbPassword = !empty($config['db_password']) ? sprintf('-p%s', $config['db_password']) : '';
        $process = $this->shellProcess->run(sprintf(
            "mysql -u %s %s -h%s -P%s %s -e 'SELECT 1'",
            $config['db_user'],
            $dbPassword,
            $this->getConnectionHost($config),
            $config['db_port'],
            $config['db_name'],
        ));
        return str_replace("\n", "", $process->getOutput()) === '11';
    }

    /**
     * @inheritDoc
     */
    public function getCode(): string
    {
        return 'mysqldump';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'Database located at current server. Use regular mysqldump command';
    }

    /**
     * @inheritDoc
     */
    public function askConfig(InputOutput $inputOutput): array
    {
        $config = [];

        $config['db_host'] = $inputOutput->ask('Host', 'localhost', self::validateRequired(...));
        $config['db_user'] = $inputOutput->ask('User', 'root', self::validateRequired(...));
        $config['db_password'] = $inputOutput->askHidden('Password');
        $config['db_name'] = $inputOutput->ask('Database name', null, self::validateRequired(...));
        $config['db_port'] = $inputOutput->ask('Port ', '3306', self::validateRequired(...));

        return $config;
    }

    /**
     * @param string $engine
     * @return bool
     */
    public function support(string $engine): bool
    {
        return $engine === MysqlEngine::ENGINE_CODE;
    }
}
