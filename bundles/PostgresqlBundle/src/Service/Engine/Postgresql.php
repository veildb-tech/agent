<?php

declare(strict_types=1);

namespace DbManager\PostgresqlBundle\Service\Engine;

use App\Service\Engine\AbstractEngine;

class Postgresql extends AbstractEngine
{
    /**
     * Engine const
     */
    public const ENGINE_CODE = 'pgsql';

    /**
     * @return string
     */
    public function getCode(): string
    {
        return self::ENGINE_CODE;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'PostgreSQL';
    }
}
