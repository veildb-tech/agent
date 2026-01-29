<?php

declare(strict_types=1);

namespace DbManager\MariaDbBundle\DBManagement;

use DbManager\CoreBundle\DBManagement\AbstractDBManagement;
use DbManager\CoreBundle\DBManagement\DBManagementInterface;
use DbManager\MariaDbBundle\Processor;

/**
 * Mysql Dump Processor instance
 */
final class Management extends AbstractDBManagement implements DBManagementInterface
{
    /**
     * Engine const
     */
    public const DRIVER_ENGINE = Processor::DRIVER_ENGINE;

    protected function getDropLine(string $dbName): string
    {
        $credentials = $this->getCredentials($dbName);
        return sprintf(
            "mysql -u%s -p%s -h%s -P%s --skip-ssl -e 'DROP DATABASE %s'",
            ...$credentials
        );
    }

    protected function getCreateLine(string $dbName): string
    {
        $credentials = $this->getCredentials($dbName);
        return sprintf(
            "mysql -u%s -p%s -h%s -P%s --skip-ssl -e 'CREATE DATABASE %s'",
            ...$credentials
        );
    }

    protected function getImportLine(string $dbName, string $inputPath): string
    {
        $credentials = $this->getCredentials($dbName);
        if (str_contains($inputPath, '.gz')) {
            $string = "zcat < %s | grep -v '50013 DEFINER' | grep -v '^CREATE DATABASE' | grep -v '^USE' "
                . " | mysql --force -u%s -p%s -h%s -P%s %s --skip-ssl";

            return sprintf(
                $string,
                escapeshellarg($inputPath),
                ...$credentials
            );
        } else {
            return sprintf(
                "mysql --force -u%s -p%s -h%s -P%s %s --skip-ssl < %s",
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
        $dumpString = $this->appConfig->isGzipEnabled()
            ? 'mysqldump -u%s -p%s -h%s -P%s %s --skip-ssl | gzip > %s'
            : 'mysqldump -u%s -p%s -h%s -P%s %s --skip-ssl > %s';
        return sprintf(
            $dumpString,
            ...[
                ...$credentials,
                escapeshellarg($outputPath)
            ]
        );
    }
}
