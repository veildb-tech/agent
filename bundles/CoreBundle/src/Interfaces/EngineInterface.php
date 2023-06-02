<?php

declare(strict_types=1);

namespace DbManager\CoreBundle\Interfaces;

interface EngineInterface
{
    /**
     * Driver engine
     */
    public const DRIVER_ENGINE = '';

    /**
     * @param RuleManagerInterface  $rules
     * @param TempDatabaseInterface $tempDatabase
     *
     * @return void
     */
    public function execute(RuleManagerInterface $rules, TempDatabaseInterface $tempDatabase): void;
}
