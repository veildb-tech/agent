<?php

declare(strict_types=1);

namespace DbManager\CoreBundle\Service;

use DbManager\CoreBundle\Interfaces\EngineInterface;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Connection;

/**
 * AbstractEngineProcessor Class
 */
abstract class AbstractEngineProcessor implements EngineInterface
{
    /**
     * @var Connection
     */
    protected Connection $connection;

    /**
     * Get DB Connection
     *
     * @param string $dbName
     *
     * @return Connection
     */
    protected function getDbConnection(string $dbName): Connection
    {
        $capsule = new Manager();
        $capsule->addConnection([
            'driver'    => static::DRIVER_ENGINE,
            'host'      => env('DATABASE_HOST'),
            'database'  => $dbName,
            'username'  => env('DATABASE_USER'),
            'password'  => env('DATABASE_PASSWD'),
        ]);

        return $capsule->getConnection();
    }
}
