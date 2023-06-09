<?php

declare(strict_types=1);

namespace DbManager\CoreBundle;

use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use DbManager\CoreBundle\DependencyInjection\DbManagerCoreExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

class DbManagerCoreBundle extends AbstractBundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return new DbManagerCoreExtension($this);
    }
}
