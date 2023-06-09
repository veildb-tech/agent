<?php

declare(strict_types=1);

namespace DbManager\CoreBundle\DumpProcessor;

use DbManager\CoreBundle\Exception\NoSuchEngineException;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Dump Processor Factory
 */
final class DumpProcessorFactory
{
    /**
     * @param ContainerInterface $container
     */
    public function __construct(private readonly ContainerInterface $container)
    {
    }

    /**
     * @return DumpProcessorInterface
     * @throws NoSuchEngineException
     */
    public function create(): DumpProcessorInterface
    {
        $engine = env('DATABASE_ENGINE');

        return $this->getEngine($engine);
    }

    /**
     * Retrieve engine service according to provided engine name
     *
     * @param string $engine
     *
     * @return DumpProcessorInterface
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
        if (!($engine instanceof DumpProcessorInterface)) {
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
        return sprintf("db_manager_core.dump.engines.%s", $engine);
    }
}
