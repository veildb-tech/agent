<?php

declare(strict_types=1);

namespace App\Service\Database;

use App\Service\AppConfig;
use App\ServiceApi\Actions\GetDatabaseRules;
use App\ServiceApi\Actions\SendDbStructure;
use DbManager\CoreBundle\DbProcessorFactory;
use DbManager\CoreBundle\Exception\EngineNotSupportedException;
use DbManager\CoreBundle\Exception\NoSuchEngineException;
use DbManager\CoreBundle\Service\DbDataManager;
use Doctrine\DBAL\Exception;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class Analyzer
{
    /**
     * @param AppConfig $appConfig
     * @param SendDbStructure $sendDbStructure
     * @param GetDatabaseRules $getDatabaseRules
     * @param DbProcessorFactory $processorFactory
     */
    public function __construct(
        private readonly AppConfig $appConfig,
        private readonly SendDbStructure $sendDbStructure,
        private readonly GetDatabaseRules $getDatabaseRules,
        private readonly DbProcessorFactory $processorFactory
    ) {
    }

    /**
     * @param string $databaseUid
     * @param string $tempDatabase
     *
     * @return void
     * @throws ClientExceptionInterface
     * @throws EngineNotSupportedException
     * @throws NoSuchEngineException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    public function process(string $databaseUid, string $tempDatabase): void
    {
        $dbManager = new DbDataManager(
            array_merge(
                [
                    'name' => $tempDatabase,
                ],
                $this->getDbInfo($databaseUid)
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
            if (count($dbData)) {
                $data['engine'] = $dbData['engine'];
                $data['platform'] = $dbData['platform'] ?? 'custom';

                return $data;
            }
        } catch (\Exception $e) {
            return $this->getDatabaseRules->get($databaseUid);
        }
        return $this->getDatabaseRules->get($databaseUid);
    }
}
