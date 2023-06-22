<?php

declare(strict_types=1);

namespace App\Service\Database;

use App\ServiceApi\Actions\GetDatabaseRules;
use App\ServiceApi\Actions\SendDbStructure;
use DbManager\CoreBundle\DbProcessorFactory;
use DbManager\CoreBundle\Exception\EngineNotSupportedException;
use DbManager\CoreBundle\Exception\NoSuchEngineException;
use DbManager\CoreBundle\Service\DbDataManager;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class Analyzer
{
    /**
     * @param SendDbStructure $sendDbStructure
     * @param GetDatabaseRules $getDatabaseRules
     * @param DbProcessorFactory $processorFactory
     */
    public function __construct(
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
     */
    public function process(string $databaseUid, string $tempDatabase): void
    {
        $dbManager = new DbDataManager(
            array_merge(
                [
                    'name' => $tempDatabase
                ],
                $this->getDatabaseRules->get($databaseUid)
            )
        );

        $structure = $this->processorFactory->create($dbManager->getEngine())->getDbStructure($dbManager);
        $this->sendDbStructure->execute($databaseUid, $structure);
    }
}
