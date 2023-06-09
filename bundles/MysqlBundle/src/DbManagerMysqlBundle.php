<?php

declare(strict_types=1);

namespace DbManager\MysqlBundle;

use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use DbManager\MysqlBundle\DependencyInjection\DbManagerMysqlExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

class DbManagerMysqlBundle extends AbstractBundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return new DbManagerMysqlExtension($this);
    }
}
