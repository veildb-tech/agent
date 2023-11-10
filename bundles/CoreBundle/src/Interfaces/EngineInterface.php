<?php

declare(strict_types=1);

namespace DbManager\CoreBundle\Interfaces;

use Doctrine\DBAL\Exception;

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
    public function process(DbDataManagerInterface $dbDataManager): void;

    /**
     * @return array
     */
    public function getErrors(): array;

    /**
     * Get DB structure
     * return array:
     * [
     *  <table name> => [
     *      '<column>'
     *      '<column>'
     *       ...
     *  ]
     * ]
     *
     * @param DbDataManagerInterface $dbDataManager
     *
     * @return array
     * @throws Exception
     */
    public function getDbStructure(DbDataManagerInterface $dbDataManager): array;
}
