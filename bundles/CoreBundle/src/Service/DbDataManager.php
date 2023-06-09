<?php

declare(strict_types=1);

namespace DbManager\CoreBundle\Service;

use ArrayIterator;
use ArrayObject;
use DbManager\CoreBundle\Interfaces\DbDataManagerInterface;

class DbDataManager extends ArrayObject implements DbDataManagerInterface
{
    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return $this->offsetGet('name');
    }

    /**
     * @inheritdoc
     */
    public function setName(string $name): DbDataManagerInterface
    {
        $this->offsetSet('name', $name);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setEngine(string $engine): DbDataManagerInterface
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
    public function setRules(array $rules): DbDataManagerInterface
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
