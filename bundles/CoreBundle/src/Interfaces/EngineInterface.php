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
     * @param DbDataManagerInterface $dbDataManager
     *
     * @return void
     */
    public function execute(DbDataManagerInterface $dbDataManager): void;
}
