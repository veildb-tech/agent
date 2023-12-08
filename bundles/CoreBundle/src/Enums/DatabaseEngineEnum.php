<?php

declare(strict_types=1);

namespace DbManager\CoreBundle\Enums;

enum DatabaseEngineEnum: string
{
    case MYSQL = 'mysql';
    case POSTGRES = 'pgsql';
    case SQL_LITE = 'sqllite';
}
