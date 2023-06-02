<?php

declare(strict_types=1);

namespace DbManager\CoreBundle\Service;

use ArrayIterator;
use ArrayObject;
use DbManager\CoreBundle\Interfaces\RuleManagerInterface;

class RuleManager extends ArrayObject implements RuleManagerInterface
{
    /**
     * @inheritdoc
     */
    public function setEngine(string $engine): RuleManagerInterface
    {
        $this->offsetSet('engine', $engine);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getEngine(): string
    {
        return $this->offsetGet('engine');
    }

    /**
     * @inheritdoc
     */
    public function setRules(array $rules): RuleManagerInterface
    {
        $this->offsetSet('rules', $rules);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getRules(): array
    {
        return $this->offsetGet('rules');
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
