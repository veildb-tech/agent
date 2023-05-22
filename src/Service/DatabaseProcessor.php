<?php

namespace App\Service;

use DbManager\CoreBundle\Exception\NoSuchEngineException;
use DbManager\CoreBundle\Service\TempDatabase;
use DbManager\CoreBundle\Service\RuleManager;
use DbManager\CoreBundle\Processor;
use App\ServiceApi\Actions\GetDatabaseRules;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class DatabaseProcessor
{
    /**
     * @param TempDatabase $tempDatabase
     * @param RuleManager $ruleManager
     * @param GetDatabaseRules $getDatabaseRules
     * @param Processor $processor
     */
    public function __construct(
        private TempDatabase $tempDatabase,
        private RuleManager $ruleManager,
        private GetDatabaseRules $getDatabaseRules,
        private Processor $processor
    ) {
    }

    /**
     * @param string $databaseUid
     * @param string $tempDatabase
     * @return void
     * @throws NoSuchEngineException
     * @throws DecodingExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function process(string $databaseUid, string $tempDatabase): void
    {
        $this->tempDatabase->setName($tempDatabase);
        $rules = $this->getDatabaseRules->get($databaseUid);
        $this->ruleManager->set($rules);

        $this->processor->execute($rules['engine'], $this->ruleManager, $this->tempDatabase);
    }
}
