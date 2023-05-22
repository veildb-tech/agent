<?php

declare(strict_types=1);

namespace DbManager\CoreBundle;

use DbManager\CoreBundle\Interfaces\EngineInterface;
use DbManager\CoreBundle\Interfaces\RuleManagerInteface;
use DbManager\CoreBundle\Interfaces\TempDatabaseInterface;
use DbManager\CoreBundle\Exception\NoSuchEngineException;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Processor
{
    /**
     * @param ContainerInterface $container
     */
    public function __construct(private readonly ContainerInterface $container)
    {
    }

    /**
     * @throws NoSuchEngineException
     */
    public function execute(
        string $engine,
        RuleManagerInteface $ruleManagerInterface,
        TempDatabaseInterface $tempDatabase
    ): void {
        $processor = $this->getEngine($engine);
        $processor->execute($ruleManagerInterface, $tempDatabase);
    }

    /**
     * Retrieve engine service acording to provided engine name
     *
     * @throws NoSuchEngineException
     */
    private function getEngine(string $engine): EngineInterface
    {
        $serviceName = $this->getServiceName($engine);

        if (!$this->container->has($serviceName)) {
            throw new NoSuchEngineException(sprintf("No such engine %s", $engine));
        }

        return $this->container->get($serviceName);
    }

    /**
     * Due to service name agreement return service name
     *
     * @param string $engine
     * @return string
     */
    private function getServiceName(string $engine): string
    {
        return sprintf("db_manager_core.engines.%s", $engine);
    }
}
