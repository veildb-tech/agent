<?php

declare(strict_types=1);

namespace DbManager\CoreBundle\DBManagement;

use DbManager\CoreBundle\Exception\ShellProcessorException;
use DbManager\CoreBundle\Interfaces\DbDataManagerInterface;

interface DBManagementInterface
{
    /**
     * Driver engine
     */
    public const DRIVER_ENGINE = '';

    /**
     * Create DB
     *
     * @param DbDataManagerInterface $database
     *
     * @return bool
     * @throws ShellProcessorException
     */
    public function create(DbDataManagerInterface $database): bool;

    /**
     * Drop DB
     *
     * @param DbDataManagerInterface $database
     *
     * @return bool
     * @throws ShellProcessorException
     */
    public function drop(DbDataManagerInterface $database): bool;

    /**
     * @param DbDataManagerInterface $database
     *
     * @return string
     * @throws ShellProcessorException
     */
    public function dump(DbDataManagerInterface $database): string;

    /**
     * Import DB
     *
     * @param DbDataManagerInterface $database
     *
     * @return string
     * @throws ShellProcessorException
     */
    public function import(DbDataManagerInterface $database): string;
}