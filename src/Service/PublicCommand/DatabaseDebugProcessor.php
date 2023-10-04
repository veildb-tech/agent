<?php

declare(strict_types=1);

namespace App\Service\PublicCommand;

use App\Enum\LogStatusEnum;
use App\Exception\NoSuchMethodException;
use App\Service\AppLogger;
use App\ServiceApi\Entity\DatabaseDump;
use Psr\Cache\InvalidArgumentException;
use App\Service\DumpManagement;
use App\ServiceApi\Actions\GetDatabaseRules;
use DbManager\CoreBundle\DbProcessorFactory;
use DbManager\CoreBundle\Service\DbDataManager;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use DbManager\CoreBundle\DBManagement\DBManagementFactory;

class DatabaseDebugProcessor extends AbstractCommand
{
    /**
     * @param AppLogger $appLogger
     * @param DatabaseDump $databaseDump
     * @param DumpManagement $dumpManagement
     * @param DBManagementFactory $dbManagementFactory
     * @param DbProcessorFactory $processorFactory
     * @param GetDatabaseRules $getDatabaseRules
     */
    public function __construct(
        private readonly AppLogger $appLogger,
        private readonly DatabaseDump $databaseDump,
        private readonly DumpManagement $dumpManagement,
        private readonly DBManagementFactory $dbManagementFactory,
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
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws NoSuchMethodException
     * @throws \Exception
     * @throws InvalidArgumentException
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
                    'inputFile' => '/var/www/bridgedigital/db-manager/server/dumps/untouched/018a50ff-0af9-70ce-a8b1-b988cbf15fa5/1694441058.sql',
                    'backup_path' => '/var/www/bridgedigital/db-manager/server/dumps/untouched/018a50ff-0af9-70ce-a8b1-b988cbf15fa5/1694441058-1.sql'
                ]
            )
        );
//        $dbManagement = $this->dbManagementFactory->create();
//        $dbManagement->create($database);




        $this->processorFactory->create($database->getEngine())->process($database);

return;
        $scheduledData = $this->databaseDump->getScheduled();
        if (!empty($scheduledData)) {
            $dbuuid = $scheduledData['db']['uid'];
            $dumpuuid = $scheduledData['uuid'];
            if (empty($dumpuuid) || empty($dbuuid)) {
                throw new \Exception("Something went wrong. Scheduled uuid and database uuid is required");
            }

            $this->appLogger->logToService(
                $dumpuuid,
                LogStatusEnum::PROCESSING->value,
                "Preparing backup"
            );
            $originFile = $this->dumpManagement->createDump($dbuuid);
            $destinationFile = $this->dumpManagement->getDestinationFilePath($dbuuid);

            $tempDatabase = 'temp_' . time();

            $database = new DbDataManager(
                array_merge(
                    $this->getDatabaseRules->get($dbuuid),
                    [
                        'name' => $tempDatabase,
                        'inputFile' => $originFile->getPathname(),
                        'backup_path' => $destinationFile->getPathname()
                    ]
                )
            );
            $dbManagement = $this->dbManagementFactory->create();

            $this->appLogger->logToService(
                $dumpuuid,
                LogStatusEnum::PROCESSING->value,
                "Creating temporary table"
            );
            $dbManagement->create($database);

            $this->appLogger->logToService(
                $dumpuuid,
                LogStatusEnum::PROCESSING->value,
                "Import backup to temporary table"
            );
            $dbManagement->import($database);

            $this->appLogger->logToService(
                $dumpuuid,
                LogStatusEnum::PROCESSING->value,
                "Processing database dump"
            );
            $this->processorFactory->create($database->getEngine())->process($database);

            $this->appLogger->logToService(
                $dumpuuid,
                LogStatusEnum::PROCESSING->value,
                "Creating new dump file"
            );
            $dbManagement->dump($database);

            $this->appLogger->logToService(
                $dumpuuid,
                LogStatusEnum::PROCESSING->value,
                "Dropping temporary database"
            );
            $dbManagement->drop($database);

            $this->appLogger->logToService(
                $dumpuuid,
                LogStatusEnum::SUCCESS->value,
                "Completed!"
            );
            $this->databaseDump->updateByUuid($dumpuuid, 'ready', $destinationFile->getFilename());
        }
    }
}
