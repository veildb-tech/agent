<?php

declare(strict_types=1);

namespace DbManager\CoreBundle\Service;

use DbManager\CoreBundle\Interfaces\EngineInterface;
use Illuminate\Database\Capsule\Manager as CapsuleManager;
use Illuminate\Database\Connection;

abstract class AbstractEngineProcessor implements EngineInterface
{
    /**
     * Get DB Connection
     *
     * @param string $dbName
     *
     * @return Connection
     */
    protected function getDbConnection(string $dbName): Connection
    {
        $capsule = new CapsuleManager;
        $capsule->addConnection([
            'driver'    => static::DRIVER_ENGINE,
            'host'      => env('DATABASE_HOST'),
            'database'  => $dbName,
            'username'  => env('DATABASE_USER'),
            'password'  => env('DATABASE_PASSWD'),
        ]);

        $db = $capsule->getConnection();
        $db->statement('SET FOREIGN_KEY_CHECKS=0');

        return $db;
    }
}
