<?php

declare(strict_types=1);

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
     * @param TempDatabase     $tempDatabase
     * @param RuleManager      $ruleManager
     * @param GetDatabaseRules $getDatabaseRules
     * @param Processor        $processor
     */
    public function __construct(
        private readonly TempDatabase     $tempDatabase,
        private readonly RuleManager      $ruleManager,
        private readonly GetDatabaseRules $getDatabaseRules,
        private readonly Processor        $processor
    ) {
    }

    /**
     * @param string $databaseUid
     * @param string $tempDatabase
     *
     * @return void
     *
     * @throws NoSuchEngineException
     * @throws DecodingExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function process(string $databaseUid, string $tempDatabase): void
    {
        $this->tempDatabase->setName($tempDatabase);

        /**
         * TODO: Change functionality getDatabaseRules->get, better to return RuleManagerInterface
         */
        $rules = $this->getDatabaseRules->get($databaseUid);

        $this->ruleManager->setEngine($rules['engine']);
        $this->ruleManager->setRules($rules['tables']);

        $this->processor->execute($this->ruleManager, $this->tempDatabase);
    }
}
