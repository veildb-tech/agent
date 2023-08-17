<?php

declare(strict_types=1);

namespace DbManager\MysqlBundle\DBManagement;

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
            "mysql -u%s -p%s -h%s -P%s -e 'DROP DATABASE %s'",
            ...$credentials
        );
    }

    protected function getCreateLine(string $dbName): string
    {
        $credentials = $this->getCredentials($dbName);
        return sprintf(
            "mysql -u%s -p%s -h%s -P%s -e 'CREATE DATABASE %s'",
            ...$credentials
        );
    }

    protected function getImportLine(string $dbName, string $inputPath): string
    {
        $credentials = $this->getCredentials($dbName);
        if (str_contains($inputPath, '.gz')) {
            $string = "zcat < %s | grep -v '50013 DEFINER' | grep -v '^CREATE DATABASE' | grep -v '^USE' "
                . " | mysql -u%s -p%s -h%s -P%s %s";

            return sprintf(
                $string,
                escapeshellarg($inputPath),
                ...$credentials
            );
        } else {
            return sprintf(
                "mysql -u%s -p%s -h%s -P%s %s < %s",
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
            'mysqldump -u%s -p%s -h%s -P%s %s > %s',
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
}
