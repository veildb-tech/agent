<?php

declare(strict_types=1);

namespace DbManager\CoreBundle\DBManagement;

use DbManager\CoreBundle\Exception\NoSuchEngineException;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Dump Processor Factory
 */
final class DBManagementFactory
{
    /**
     * @param ContainerInterface $container
     */
    public function __construct(private readonly ContainerInterface $container)
    {
    }

    /**
     * @param string|null $engine
     *
     * @return DBManagementInterface
     * @throws NoSuchEngineException
     */
    public function create(string $engine = null): DBManagementInterface
    {
        return $this->getEngine($engine);
    }

    /**
     * Retrieve engine service according to provided engine name
     *
     * @param string $engine
     *
     * @return DBManagementInterface
     *
     * @throws NoSuchEngineException
     */
    private function getEngine(string $engine): object
    {
        $serviceName = $this->getServiceName($engine);

        if (!$this->container->has($serviceName)) {
            throw new NoSuchEngineException(sprintf("No such engine %s", $engine));
        }

        $engine = $this->container->get($serviceName);
        if (!($engine instanceof DBManagementInterface)) {
            throw new InvalidArgumentException('The engine must be instance of EngineInterface');
        }

        return $engine;
    }

    /**
     * Due to service name agreement return service name
     *
     * @param string $engine
     *
     * @return string
     */
    private function getServiceName(string $engine): string
    {
        return sprintf("db_manager_core.management.engines.%s", $engine);
    }
}
