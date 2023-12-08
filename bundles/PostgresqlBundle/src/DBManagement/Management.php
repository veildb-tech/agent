<?php

declare(strict_types=1);

namespace DbManager\PostgresqlBundle\DBManagement;

use DbManager\CoreBundle\DBManagement\AbstractDBManagement;
use DbManager\CoreBundle\DBManagement\DBManagementInterface;

/**
 * Mysql Dump Processor instance
 */
final class Management extends AbstractDBManagement implements DBManagementInterface
{
    protected function getDropLine(string $dbName): string
    {
        $credentials = $this->getCredentials($dbName);
        return sprintf(
            "psql postgresql://%s%s@%s:%s/ -c 'DROP DATABASE %s WITH (FORCE)'",
            ...$credentials
        );
    }

    protected function getCreateLine(string $dbName): string
    {
        $credentials = $this->getCredentials($dbName);
        return sprintf(
            "psql postgresql://%s%s@%s:%s/ -c 'CREATE DATABASE %s'",
            ...$credentials
        );
    }

    protected function getImportLine(string $dbName, string $inputPath): string
    {
        $credentials = $this->getCredentials($dbName);

        if (str_contains($inputPath, '.gz')) {
            $string = "zcat < %s | grep -v '50013 DEFINER' | grep -v '^CREATE DATABASE' | grep -v '^USE' "
                . " | psql postgresql://%s%s@%s:%s/%s";

            return sprintf(
                $string,
                escapeshellarg($inputPath),
                ...$credentials
            );
        } else {
            return sprintf(
                "psql postgresql://%s%s@%s:%s/%s < %s",
                ...[
                    ...$credentials,
                    escapeshellarg($inputPath)
                ]
            );
        }
    }

    protected function getDumpLine(string $dbName, string $outputPath): string
    {
        $credentials = $this->getCredentials($dbName);
        return sprintf(
            'pg_dump postgresql://%s%s@%s:%s/%s > %s',
            ...[
                ...$credentials,
                escapeshellarg($outputPath)
            ]
        );
    }

    protected function getCredentials(string $dbName): array
    {
        return [
            $this->appConfig->getConfig('work_db_user'),
            $this->getPassword(),
            $this->appConfig->getConfig('work_db_host'),
            $this->appConfig->getConfig('work_db_port'),
            escapeshellarg($dbName),
        ];
    }

    /**
     * Get DB password
     *
     * @return string
     */
    protected function getPassword(): string
    {
        return $this->appConfig->getConfig('work_db_password')
            ? ":" . $this->appConfig->getConfig('work_db_password')
            : '';
    }
}
