<?php

declare(strict_types=1);

namespace App\Service;

use DbManager\CoreBundle\Exception\NoSuchEngineException;
use DbManager\CoreBundle\Service\TempDatabase;
use DbManager\CoreBundle\Processor;
use App\ServiceApi\Actions\GetDatabaseRules;

class DatabaseProcessor
{
    /**
     * @param TempDatabase     $tempDatabase
     * @param GetDatabaseRules $getDatabaseRules
     * @param Processor        $processor
     */
    public function __construct(
        private readonly TempDatabase     $tempDatabase,
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
     */
    public function process(string $databaseUid, string $tempDatabase): void
    {
        $this->tempDatabase->setName($tempDatabase);

        $ruleManager = $this->getDatabaseRules->get($databaseUid);

        $this->processor->execute($ruleManager, $this->tempDatabase);
    }
}
