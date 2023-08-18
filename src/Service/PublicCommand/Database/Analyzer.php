<?php

declare(strict_types=1);

namespace App\Service\PublicCommand\Database;

use App\Service\AppConfig;
use App\Service\PublicCommand\AbstractCommand;
use App\Service\DumpManagement;
use App\ServiceApi\Actions\GetDatabaseRules;
use App\ServiceApi\Actions\SendDbStructure;
use DbManager\CoreBundle\DBManagement\DBManagementFactory;
use DbManager\CoreBundle\DbProcessorFactory;
use DbManager\CoreBundle\Exception\EngineNotSupportedException;
use DbManager\CoreBundle\Exception\NoSuchEngineException;
use DbManager\CoreBundle\Service\DbDataManager;
use Doctrine\DBAL\Exception;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Analyzer extends AbstractCommand
{
    /**
     * @param AppConfig $appConfig
     * @param SendDbStructure $sendDbStructure
     * @param GetDatabaseRules $getDatabaseRules
     * @param DbProcessorFactory $processorFactory
     * @param DBManagementFactory $dbManagementFactory
     */
    public function __construct(
        private readonly AppConfig $appConfig,
        private readonly SendDbStructure $sendDbStructure,
        private readonly GetDatabaseRules $getDatabaseRules,
        private readonly DbProcessorFactory $processorFactory,
        private readonly DBManagementFactory $dbManagementFactory,
        private readonly DumpManagement $dumpManagement
    ) {
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws EngineNotSupportedException
     * @throws Exception
     * @throws NoSuchEngineException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws InvalidArgumentException
     */
    public function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->initInputOutput($input, $output);

        $tempDatabase = $input->getOption('db');
        $databaseUid = $input->getOption('uid');

        if (!$tempDatabase) {
            $this->createTempDbAndProcess($databaseUid);
        } else {
            $this->process($databaseUid, $tempDatabase);
        }
    }

    /**
     * Process without database
     *
     * @param string $databaseUid
     * @return void
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function createTempDbAndProcess(string $databaseUid): void
    {
        try {
            $dbInfo = $this->getDbInfo($databaseUid);
            $tempDatabase = 'temp_' . time();

            $io = $this->getInputOutput();
            $io->info("Dump database...");
            $file = $this->dumpManagement->createDump($databaseUid);

            $io->info("Import temporary database...");
            $dbManager = new DbDataManager(
                array_merge(
                    $dbInfo,
                    [
                        'name' => $tempDatabase,
                        'inputFile' => $file->getPathname()
                    ]
                )
            );

            $dbManagement = $this->dbManagementFactory->create();
            $dbManagement->create($dbManager);
            $dbManagement->import($dbManager);

            $io->info("Analyzing...");
            $this->process($databaseUid, $tempDatabase);

            $io->info("Drop temporary database...");
            $dbManagement->drop($dbManager);
        } catch (\Exception $exception) {
            $this->getInputOutput()->error($exception->getMessage());
        }
    }

    /**
     * Process with existing database
     *
     * @param string $databaseUid
     * @param string $tempDatabase
     * @return void
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws EngineNotSupportedException
     * @throws Exception
     * @throws NoSuchEngineException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws InvalidArgumentException
     */
    public function process(string $databaseUid, string $tempDatabase): void
    {
        $dbInfo = $this->getDbInfo($databaseUid);

        $dbManager = new DbDataManager(
            array_merge(
                $dbInfo,
                [
                    'name' => $tempDatabase,
                ]
            )
        );

        $structure = $this->processorFactory->create($dbManager->getEngine())->getDbStructure($dbManager);
        $this->sendDbStructure->execute($databaseUid, $structure);
    }

    /**
     * Get DB info
     *
     * @param string $databaseUid
     *
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws \Exception
     */
    private function getDbInfo(string $databaseUid): array
    {
        try {
            $dbData = $this->appConfig->getDatabaseConfig($databaseUid);
            if (!$dbData['platform']) {
                $dbData['platform'] = 'custom';
            }

            return $dbData;
        } catch (\Exception $exception) {
            return $this->getDatabaseRules->get($databaseUid);
        }
    }
}
