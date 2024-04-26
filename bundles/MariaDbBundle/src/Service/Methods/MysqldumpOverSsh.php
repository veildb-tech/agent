<?php

declare(strict_types=1);

namespace DbManager\MariaDbBundle\Service\Methods;

use DbManager\MariaDbBundle\Service\Engine\MariaDb as MariaDbEngine;
use DbManager\MysqlBundle\Service\Methods\MysqldumpOverSsh as MySQLEngineDumpOverSsh;

/**
 * TODO: maybe better to use ssh2_shell to connect by SSH instead of Process
 */
class MysqldumpOverSsh extends MySQLEngineDumpOverSsh
{
    /**
     * @param string $engine
     * @return bool
     */
    public function support(string $engine): bool
    {
        return $engine === MariaDbEngine::ENGINE_CODE;
    }
}
