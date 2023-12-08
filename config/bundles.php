<?php

return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Symfony\Bundle\MakerBundle\MakerBundle::class => ['dev' => true],
    DbManager\MysqlBundle\DbManagerMysqlBundle::class => ['all' => true],
    DbManager\CoreBundle\DbManagerCoreBundle::class => ['all' => true],
    DbManager\TestBundle\DbManagerTestBundle::class => ['dev' => true, 'test' => true],
    DbManager\MagentoBundle\DbManagerMagentoBundle::class => ['dev' => true, 'test' => true],
    DbManager\PostgresqlBundle\DbManagerPostgresqlBundle::class => ['all' => true],
    Symfony\Bundle\MonologBundle\MonologBundle::class => ['all' => true],
];
