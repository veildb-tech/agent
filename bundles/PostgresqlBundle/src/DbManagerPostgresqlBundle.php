<?php

declare(strict_types=1);

namespace DbManager\PostgresqlBundle;

use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use DbManager\PostgresqlBundle\DependencyInjection\DbManagerPostgresqlExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

class DbManagerPostgresqlBundle extends AbstractBundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return new DbManagerPostgresqlExtension($this);
    }
}
