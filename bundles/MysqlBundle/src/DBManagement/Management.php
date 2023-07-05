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
        return sprintf(
            "mysql -u%s -p%s -h%s -P%s -e 'DROP DATABASE %s'",
            $this->appConfig->getConfig('work_db_user'),
            $this->getPassword(),
            $this->appConfig->getConfig('work_db_host'),
            $this->appConfig->getConfig('work_db_port'),
            escapeshellarg($dbName)
        );
    }

    protected function getCreateLine(string $dbName): string
    {
        return sprintf(
            "mysql -u%s -p%s -h%s -P%s -e 'CREATE DATABASE %s'",
            $this->appConfig->getConfig('work_db_user'),
            $this->getPassword(),
            $this->appConfig->getConfig('work_db_host'),
            $this->appConfig->getConfig('work_db_port'),
            escapeshellarg($dbName)
        );
    }

    protected function getImportLine(string $dbName, string $inputPath): string
    {
        if (str_contains($inputPath, '.gz')) {
            $string = "zcat < %s | grep -v '50013 DEFINER' | grep -v '^CREATE DATABASE' | grep -v '^USE' "
                . " | mysql -u%s -p%s -h%s -P%s %s";

            return sprintf(
                $string,
                escapeshellarg($inputPath),
                $this->appConfig->getConfig('work_db_user'),
                $this->getPassword(),
                $this->appConfig->getConfig('work_db_host'),
                $this->appConfig->getConfig('work_db_port'),
                escapeshellarg($dbName)
            );
        } else {
            return sprintf(
                "mysql -u%s -p%s -h%s -P%s %s < %s",
                $this->appConfig->getConfig('work_db_user'),
                $this->getPassword(),
                $this->appConfig->getConfig('work_db_host'),
                $this->appConfig->getConfig('work_db_port'),
                escapeshellarg($dbName),
                escapeshellarg($inputPath)
            );
        }
    }

    protected function getDumpLine(string $dbName, string $outputPath): string
    {
        return sprintf(
            'mysqldump -h%s -p%s -u%s -P%s %s > %s',
            $this->appConfig->getConfig('work_db_host'),
            $this->getPassword(),
            $this->appConfig->getConfig('work_db_user'),
            $this->appConfig->getConfig('work_db_port'),
            escapeshellarg($dbName),
            escapeshellarg($outputPath),
        );
    }
}
