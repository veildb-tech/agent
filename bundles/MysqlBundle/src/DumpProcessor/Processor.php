<?php

declare(strict_types=1);

namespace DbManager\MysqlBundle\DumpProcessor;

use DbManager\CoreBundle\DumpProcessor\AbstractDumpProcessor;
use DbManager\CoreBundle\DumpProcessor\DumpProcessorInterface;

/**
 * Mysql Dump Processor instance
 */
final class Processor extends AbstractDumpProcessor implements DumpProcessorInterface
{
    protected function getDumpCommandLine(string $dbName, string $outputPath): string
    {
        return sprintf(
            'mysqldump -h%s -p%s -u%s -P%s %s > %s',
            escapeshellarg(env('DATABASE_HOST')),
            escapeshellarg(env('DATABASE_PASSWD')),
            escapeshellarg(env('DATABASE_USER')),
            escapeshellarg(env('DATABASE_PORT')),
            escapeshellarg($dbName),
            escapeshellarg($outputPath),
        );
    }
}
