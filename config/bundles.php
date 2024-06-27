<?php

use DbManager\MariaDbBundle\Service\Engine\MariaDb;
use DbManager\MysqlBundle\Service\Engine\Mysql;
use DbManager\PostgresqlBundle\Service\Engine\Postgresql;

$engines = [];

foreach ([
    MariaDb::class,
    Mysql::class,
    Postgresql::class,
] as $engineClass) {
    $engineCode = $engineClass::ENGINE_CODE;
    if (is_file(sprintf('%s/../.env.%s', __DIR__, $engineCode))) {
        $engines[] = $engineClass::ENGINE_CODE;
    }
}

return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Symfony\Bundle\MakerBundle\MakerBundle::class => ['dev' => true],
    DbManager\MysqlBundle\DbManagerMysqlBundle::class => ['all' => in_array(MariaDb::ENGINE_CODE, $engines)],
    DbManager\MariaDbBundle\DbManagerMariaDbBundle::class => ['all' => in_array(MariaDb::ENGINE_CODE, $engines)],
    DbManager\CoreBundle\DbManagerCoreBundle::class => ['all' => true],
    DbManager\TestBundle\DbManagerTestBundle::class => ['dev' => true, 'test' => true],
    DbManager\MagentoBundle\DbManagerMagentoBundle::class => ['all' => true],
    DbManager\PostgresqlBundle\DbManagerPostgresqlBundle::class => ['all' => in_array(Postgresql::ENGINE_CODE, $engines)],
    Symfony\Bundle\MonologBundle\MonologBundle::class => ['all' => true],
];
