<?php

declare(strict_types=1);

namespace DbManager\CoreBundle\Interfaces;

interface DbDataManagerInterface
{
    public const METHOD_TRUNCATE = 'truncate';
    public const METHOD_FAKE = 'fake';

    /**
     * Get DB name data
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Det DB Name
     *
     * @param string $name
     *
     * @return DbDataManagerInterface
     */
    public function setName(string $name): DbDataManagerInterface;

    /**
     * Set Rule engine
     *
     * @param string $engine
     *
     * @return DbDataManagerInterface
     */
    public function setEngine(string $engine): DbDataManagerInterface;

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
     * @return DbDataManagerInterface
     */
    public function setRules(array $rules): DbDataManagerInterface;

    /**
     * Get rules
     *
     * @return array
     */
    public function getRules(): array;
}
