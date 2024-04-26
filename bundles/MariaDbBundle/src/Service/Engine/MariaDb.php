<?php

declare(strict_types=1);

namespace DbManager\MariaDbBundle\Service\Engine;

use App\Service\Engine\AbstractEngine;

class MariaDb extends AbstractEngine
{
    public const ENGINE_CODE = 'mariadb';

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
        return 'MariaDB';
    }
}
