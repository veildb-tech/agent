<?php

declare(strict_types=1);

namespace DbManager\MariaDbBundle;

use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use DbManager\MariaDbBundle\DependencyInjection\DbManagerMariaDbExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

class DbManagerMariaDbBundle extends AbstractBundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return new DbManagerMariaDbExtension($this);
    }
}
