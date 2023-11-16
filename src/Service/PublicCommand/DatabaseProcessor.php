<?php

declare(strict_types=1);

namespace App\Service\PublicCommand;

use App\Enum\DumpStatusEnum;
use App\Enum\LogStatusEnum;
use App\Exception\NoSuchMethodException;
use App\Service\AppLogger;
use App\ServiceApi\Entity\DatabaseDump;
use DbManager\CoreBundle\Exception\EngineNotSupportedException;
use DbManager\CoreBundle\Exception\NoSuchEngineException;
use DbManager\CoreBundle\Exception\ShellProcessorException;
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
use Exception;

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
     */
    public function __construct(
        private readonly AppLogger $appLogger,
        private readonly DatabaseDump $databaseDump,
        private readonly DumpManagement $dumpManagement,
        private readonly DBManagementFactory $dbManagementFactory,
        private readonly DbProcessorFactory $processorFactory,
        private readonly GetDatabaseRules $getDatabaseRules,
        private readonly Analyzer $analyzer
    ) {
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws InvalidArgumentException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws EngineNotSupportedException
     * @throws NoSuchEngineException
     * @throws ShellProcessorException
     * @throws Exception
     */
    public function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->appLogger->initAppLogger($output);
        try {
            $scheduledData = $this->databaseDump->getScheduled();
        } catch (Exception $exception) {
            if ($output->isVerbose()) {
                $output->writeln($exception->getMessage());
            }
        }

        if (empty($scheduledData) || !count($scheduledData)) {
            return;
        }

        $dbUuid = $scheduledData['db']['uid'];
        $dumpUuid = $scheduledData['uuid'];
        if (empty($dumpUuid) || empty($dbUuid)) {
            throw new \Exception("Something went wrong. Scheduled uuid and database uuid is required");
        }

        try {
            $this->databaseDump->updateByUuid(
                $dumpUuid,
                DumpStatusEnum::PROCESSING->value,
                $scheduledData['filename']
            );

            $filename = $this->databaseProcess($dumpUuid, $dbUuid, $scheduledData);

            $this->databaseDump->updateByUuid(
                $dumpUuid,
                DumpStatusEnum::READY->value,
                $filename
            );
        } catch (
            ClientExceptionInterface
            | RedirectionExceptionInterface
            | ServerExceptionInterface
            | DecodingExceptionInterface
            | NoSuchMethodException
            | TransportExceptionInterface $exception
        ) {
            $this->databaseDump->updateByUuid(
                $dumpUuid,
                DumpStatusEnum::ERROR->value,
                $scheduledData['filename']
            );
            throw new \Exception("During Processing an error happened. Please check logs.");
        }
    }

    /**
     * @param string $dumpuuid
     * @param string $dbuuid
     * @param array $scheduledData
     *
     * @return string
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws InvalidArgumentException
     * @throws NoSuchMethodException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws EngineNotSupportedException
     * @throws NoSuchEngineException
     * @throws ShellProcessorException
     * @throws Exception
     */
    private function databaseProcess(string $dumpuuid, string $dbuuid, array $scheduledData): string
    {
        $this->appLogger->logToService(
            $dumpuuid,
            LogStatusEnum::PROCESSING->value,
            "Preparing backup"
        );
        $originFile      = $this->dumpManagement->createDump($dbuuid, $scheduledData['filename'] ?? '');
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

        return $destinationFile->getFilename();
    }
}
