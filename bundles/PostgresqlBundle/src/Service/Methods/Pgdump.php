<?php

declare(strict_types=1);

namespace DbManager\PostgresqlBundle\Service\Methods;

use App\Service\InputOutput;
use Exception;

class Pgdump extends PgMethod
{
    /**
     * @param array $dbConfig
     * @param string $dbUuid
     * @param string|null $filename
     * @return string
     * @throws Exception
     */
    public function execute(array $dbConfig, string $dbUuid, ?string $filename = null): string
    {
        $destFile = $this->getDestinationFile($dbUuid, $filename);
        $this->shellProcess->run(sprintf(
            "pg_dump %s > %s",
            $this->getPgsqlUrl($dbConfig),
            $destFile
        ));

        return $destFile;
    }

    /**
     * Check connection to provided database
     *
     * @param array $config
     * @return bool
     * @throws Exception
     */
    public function validate(array $config): bool
    {
        $process = $this->shellProcess->run(sprintf("psql %s -c 'SELECT 1' -At", $this->getPgsqlUrl($config)));
        return trim($process->getOutput()) === '1';
    }

    /**
     * @inheritDoc
     */
    public function getCode(): string
    {
        return 'pg_dump';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'Database located at current server. Use regular pg_dump command';
    }

    /**
     * @inheritDoc
     */
    public function askConfig(InputOutput $inputOutput, array $config = []): array
    {
        return $this->askDatabaseConfig($inputOutput, $config);
    }
}
