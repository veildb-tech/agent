<?php

declare(strict_types=1);

namespace DbManager\MysqlBundle\Service\Engine;

use App\Service\Engine\AbstractEngine;

class Mysql extends AbstractEngine
{
    public const ENGINE_CODE = 'mysql';

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
        return 'MySQL';
    }
}
