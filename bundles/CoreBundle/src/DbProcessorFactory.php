<?php

declare(strict_types=1);

namespace DbManager\CoreBundle;

use DbManager\CoreBundle\Enums\DatabaseEngineEnum;
use DbManager\CoreBundle\Exception\EngineNotSupportedException;
use DbManager\CoreBundle\Interfaces\EngineInterface;
use DbManager\CoreBundle\Exception\NoSuchEngineException;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class DbProcessorFactory
{
    /**
     * @param ContainerInterface $container
     */
    public function __construct(private readonly ContainerInterface $container)
    {
    }

    /**
     * @param string $engine
     *
     * @return EngineInterface
     * @throws EngineNotSupportedException
     * @throws NoSuchEngineException
     */
    public function create(string $engine): EngineInterface
    {
        return $this->getEngine($engine);
    }

    /**
     * Retrieve engine service according to provided engine name
     *
     * @param string $engine
     *
     * @return object
     *
     * @throws NoSuchEngineException|EngineNotSupportedException
     */
    public function getEngine(string $engine): object
    {
        $this->validate($engine);

        $serviceName = $this->getServiceName($engine);

        if (!$this->container->has($serviceName)) {
            throw new NoSuchEngineException(sprintf("No such engine %s", $engine));
        }

        $engine = $this->container->get($serviceName);
        if (!($engine instanceof EngineInterface)) {
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
        return sprintf("db_manager_core.engines.%s", $engine);
    }

    /**
     * Validate passed data
     *
     * @param string $engine
     *
     * @return void
     * @throws EngineNotSupportedException
     */
    private function validate(string $engine): void
    {
        if (!DatabaseEngineEnum::tryFrom($engine)) {
            throw new EngineNotSupportedException('The DB engine is not supported...');
        }
    }
}
