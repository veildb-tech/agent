<?php

declare(strict_types=1);

namespace DbManager\CoreBundle\DumpProcessor;

use DbManager\CoreBundle\Exception\ShellProcessorException;
use DbManager\CoreBundle\Interfaces\DbDataManagerInterface;

interface DumpProcessorInterface
{
    /**
     * @param DbDataManagerInterface $database
     *
     * @return string
     * @throws ShellProcessorException
     */
    public function execute(DbDataManagerInterface $database): string;
}