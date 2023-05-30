<?php

declare(strict_types=1);

namespace DbManager\CoreBundle\Interfaces;

interface RuleManagerInterface
{
    public const METHOD_TRUNCATE = 'truncate';
    public const METHOD_FAKE = 'fake';

    /**
     * Set Rule engine
     *
     * @param string $engine
     * @return RuleManagerInterface
     */
    public function setEngine(string $engine): RuleManagerInterface;

    /**
     * Get engine
     *
     * @return string
     */
    public function getEngine(): string;

    /**
     * Set tables
     *
     * @param array $tables
     *
     * @return RuleManagerInterface
     */
    public function setTables(array $tables): RuleManagerInterface;

    /**
     * Get tables
     *
     * @return array
     */
    public function getTables(): array;
}
