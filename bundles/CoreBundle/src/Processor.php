<?php

declare(strict_types=1);

namespace DbManager\CoreBundle;

use DbManager\CoreBundle\Enums\DatabaseEngineEnum;
use DbManager\CoreBundle\Exception\EngineNotSupportedException;
use DbManager\CoreBundle\Interfaces\DbDataManagerInterface;
use DbManager\CoreBundle\Interfaces\EngineInterface;
use DbManager\CoreBundle\Exception\NoSuchEngineException;
use Exception;
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
     * @param DbDataManagerInterface $dbDataManager
     *
     * @return void
     *
     * @throws NoSuchEngineException
     * @throws Exception
     */
    public function execute(DbDataManagerInterface $dbDataManager): void
    {
        $this->validate($dbDataManager);

        $processor = $this->getEngine($dbDataManager->getEngine());
        $processor->execute($dbDataManager);
    }

    /**
     * Retrieve engine service according to provided engine name
     *
     * @param string $engine
     *
     * @return object
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
     * @param DbDataManagerInterface $dbDataManager
     *
     * @return void
     * @throws EngineNotSupportedException
     */
    private function validate(DbDataManagerInterface $dbDataManager): void
    {
        if (
            !in_array(
                $dbDataManager->getEngine(),
                [
                    DatabaseEngineEnum::MYSQL->value,
                    DatabaseEngineEnum::POSTGRES->value,
                    DatabaseEngineEnum::SQL_LITE->value
                ]
            )
        ) {
            throw new EngineNotSupportedException('The DB engine is not supported...');
        }
    }
}
