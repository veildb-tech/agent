<?php

declare(strict_types=1);

namespace DbManager\CoreBundle\Service;

use ArrayIterator;
use DbManager\CoreBundle\Interfaces\RuleManagerInterface;

class RuleManager implements RuleManagerInterface
{
    /**
     * @var string
     */
    private string $engine = '';

    /**
     * @var array
     */
    private array $rules = [];

    /**
     * @inheritdoc
     */
    public function setEngine(string $engine): RuleManagerInterface
    {
        $this->engine = $engine;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getEngine(): string
    {
        return $this->engine;
    }

    /**
     * @inheritdoc
     */
    public function setRules(array $rules): RuleManagerInterface
    {
        $this->rules = $rules;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * Get iterable rules
     *
     * @return ArrayIterator
     */
    public function getIterableRules(): ArrayIterator
    {
        return new ArrayIterator($this->getRules());
    }
}
