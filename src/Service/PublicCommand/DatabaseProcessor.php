<?php

declare(strict_types=1);

namespace App\Service\PublicCommand;

use App\Enum\LogStatusEnum;
use App\Exception\LockException;
use App\Exception\NoSuchMethodException;
use App\Service\AppLogger;
use App\Service\LockService;
use App\ServiceApi\Entity\DatabaseDump;
use Psr\Cache\InvalidArgumentException;
use App\Service\DumpManagement;
use App\Service\PublicCommand\Database\Analyzer;
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

class DatabaseProcessor extends AbstractCommand
{
    /**
     * @param AppLogger $appLogger
     * @param DatabaseDump $databaseDump
     * @param DumpManagement $dumpManagement
     * @param DBManagementFactory $dbManagementFactory
     * @param DbProcessorFactory $processorFactory
     * @param GetDatabaseRules $getDatabaseRules
     * @param Analyzer $analyzer
     * @param LockService $lockService
     */
    public function __construct(
        private readonly AppLogger $appLogger,
        private readonly DatabaseDump $databaseDump,
        private readonly DumpManagement $dumpManagement,
        private readonly DBManagementFactory $dbManagementFactory,
        private readonly DbProcessorFactory $processorFactory,
        private readonly GetDatabaseRules $getDatabaseRules,
        private readonly Analyzer $analyzer,
        private readonly LockService $lockService
    ) {
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws InvalidArgumentException
     * @throws LockException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function execute(InputInterface $input, OutputInterface $output): void
    {
        if (!$this->lockService->isLocked()) {
            $this->lockService->lock();

            $this->appLogger->initAppLogger($output);
            try {
                $scheduledData = $this->databaseDump->getScheduled();
            } catch (\Exception $exception) {
                if ($output->isVerbose()) {
                    $output->writeln($exception->getMessage());
                }
            }
            if (!empty($scheduledData)) {
                $dbuuid = $scheduledData['db']['uid'];
                $dumpuuid = $scheduledData['uuid'];
                if (empty($dumpuuid) || empty($dbuuid)) {
                    throw new \Exception("Something went wrong. Scheduled uuid and database uuid is required");
                }

                try {
                    $this->process($dbuuid, $dumpuuid);
                } catch (\Exception $exception) {
                    $this->appLogger->logToService(
                        $dumpuuid,
                        LogStatusEnum::ERROR->value,
                        sprintf("Something went wrong during update. Msg: %s", $exception->getMessage())
                    );
                    $this->databaseDump->updateByUuid($dumpuuid, 'error');
                }
            }

            $this->lockService->unlock();
        } else {
            throw new LockException("There is another process running. Aborting...");
        }
    }

    /**
     * @param string $dbuuid
     * @param string $dumpuuid
     * @return void
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws InvalidArgumentException
     * @throws NoSuchMethodException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws \DbManager\CoreBundle\Exception\EngineNotSupportedException
     * @throws \DbManager\CoreBundle\Exception\NoSuchEngineException
     * @throws \DbManager\CoreBundle\Exception\ShellProcessorException
     * @throws \Doctrine\DBAL\Exception
     */
    private function process(string $dbuuid, string $dumpuuid): void
    {
        $this->databaseDump->updateByUuid($dumpuuid, 'processing');
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

        $dbManagement = $this->dbManagementFactory->create($database->getEngine());

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

        $processor = $this->processorFactory->create($database->getEngine(), $database->getPlatform());
        $processor->process($database);

        if ($processor->getErrors()) {
            foreach ($processor->getErrors() as $error) {
                $this->appLogger->logToService(
                    $dumpuuid,
                    LogStatusEnum::ERROR->value,
                    $error->getMessage()
                );
            }
        }

        $this->appLogger->logToService(
            $dumpuuid,
            LogStatusEnum::PROCESSING->value,
            "Creating new dump file"
        );
        $dbManagement->dump($database);

        $this->appLogger->logToService(
            $dumpuuid,
            LogStatusEnum::PROCESSING->value,
            "Analyze new database schema"
        );
        $this->analyzer->process($dbuuid, $tempDatabase);

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
