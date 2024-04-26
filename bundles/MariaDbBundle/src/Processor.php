<?php

declare(strict_types=1);

namespace DbManager\MariaDbBundle;

use DbManager\CoreBundle\Interfaces\EngineInterface;
use DbManager\MariaDbBundle\Service\Engine\MariaDb;
use DbManager\MysqlBundle\Processor as MySQLProcessor;

/**
 * MariaDB Processor instance
 */
class Processor extends MySQLProcessor implements EngineInterface
{
    /**
     * Engine const
     */
    public const DRIVER_ENGINE = MariaDb::ENGINE_CODE;
}
