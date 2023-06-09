<?php

declare(strict_types=1);

namespace App\Service;

use DbManager\CoreBundle\Exception\NoSuchEngineException;
use DbManager\CoreBundle\Service\DbDataManager;
use DbManager\CoreBundle\Processor;
use App\ServiceApi\Actions\GetDatabaseRules;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class DatabaseProcessor
{
    /**
     * @param GetDatabaseRules $getDatabaseRules
     * @param Processor        $processor
     */
    public function __construct(
        private readonly GetDatabaseRules $getDatabaseRules,
        private readonly Processor $processor
    ) {
    }

    /**
     * @param string $databaseUid
     * @param string $tempDatabase
     *
     * @return void
     * @throws NoSuchEngineException
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function process(string $databaseUid, string $tempDatabase): void
    {
        $this->processor->execute(
            new DbDataManager(
                array_merge(
                    [
                        'name' => $tempDatabase
                    ],
                    $this->getDatabaseRules->get($databaseUid)
                )
            )
        );
    }
}
