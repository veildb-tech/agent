<?php

declare(strict_types=1);

namespace DbManager\TestBundle\Service\PublicCommand;

use App\Service\PublicCommand\AbstractCommand;
use App\ServiceApi\Actions\GetDatabaseRules;
use DbManager\CoreBundle\DbProcessorFactory;
use DbManager\CoreBundle\Service\DbDataManager;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class DatabaseDebugProcessor extends AbstractCommand
{
    /**
     * @param DbProcessorFactory $processorFactory
     * @param GetDatabaseRules $getDatabaseRules
     */
    public function __construct(
        private readonly DbProcessorFactory $processorFactory,
        private readonly GetDatabaseRules $getDatabaseRules
    ) {
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws InvalidArgumentException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws \DbManager\CoreBundle\Exception\EngineNotSupportedException
     * @throws \DbManager\CoreBundle\Exception\NoSuchEngineException
     */
    public function execute(InputInterface $input, OutputInterface $output): void
    {
        $dbuuid = $input->getOption('uuid');
        $dbName = $input->getOption('db_name');
        $database = new DbDataManager(
            array_merge(
                $this->getDatabaseRules->get($dbuuid),
                [
                    'name' => $dbName,
                    'inputFile' => '',
                    'backup_path' => ''
                ]
            )
        );

        $dbManager = $this->processorFactory->create($database->getEngine(), $database->getPlatform());
        $dbManager->process($database);
    }
}
