<?php

declare(strict_types=1);

namespace DbManager\CoreBundle;

use DbManager\CoreBundle\Interfaces\EngineInterface;
use DbManager\CoreBundle\Interfaces\RuleManagerInterface;
use DbManager\CoreBundle\Interfaces\TempDatabaseInterface;
use DbManager\CoreBundle\Exception\NoSuchEngineException;
use InvalidArgumentException;
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
     * @param RuleManagerInterface  $ruleManagerInterface
     * @param TempDatabaseInterface $tempDatabase
     *
     * @return void
     * @throws NoSuchEngineException
     */
    public function execute(
        RuleManagerInterface $ruleManagerInterface,
        TempDatabaseInterface $tempDatabase
    ): void {
        $processor = $this->getEngine($ruleManagerInterface->getEngine());
        $processor->execute($ruleManagerInterface, $tempDatabase);
    }

    /**
     * Retrieve engine service according to provided engine name
     *
     * @param string $engine
     *
     * @return object
     * @throws NoSuchEngineException
     */
    private function getEngine(string $engine): object
    {
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
}
