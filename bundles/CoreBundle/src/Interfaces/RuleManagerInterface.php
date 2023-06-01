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
     * Set rules
     *
     * @param array $rules
     *
     * @return RuleManagerInterface
     */
    public function setRules(array $rules): RuleManagerInterface;

    /**
     * Get rules
     *
     * @return array
     */
    public function getRules(): array;
}
