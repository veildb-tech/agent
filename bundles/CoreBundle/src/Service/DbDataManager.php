<?php

declare(strict_types=1);

namespace DbManager\CoreBundle\Service;

use ArrayAccess;
use ArrayIterator;
use ArrayObject;
use DbManager\CoreBundle\Interfaces\DbDataManagerInterface;

/**
 * @method getBackupPath(): ?string
 */
class DbDataManager extends ArrayObject implements DbDataManagerInterface, ArrayAccess
{
    /**
     * @var array
     */
    private array $underscoreCache = [];

    /**
     * @throws \Exception
     */
    public function __call(string $method, array $args)
    {
        if (method_exists($this, $method)) {
            return $this->$method(...$args);
        }

        switch (substr($method, 0, 3)) {
            case 'get':
                $key = $this->underscore(substr($method, 3));
                return $this->offsetGet($key);
            case 'set':
                $key = $this->underscore(substr($method, 3));
                $this->offsetSet($key, $args[0] ?? null);
                return true;
            case 'has':
                $key = $this->underscore(substr($method, 3));
                return $this->offsetExists($key);
        }

        throw new \Exception(sprintf("The method %s is undefined.", $method));
    }

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
    public function setPlatform(string $platform): DbDataManagerInterface
    {
        $this->offsetSet('platform', $platform);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getPlatform(): string
    {
        return $this->offsetGet('platform');
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

    /**
     * Converts field names for setters and getters
     *
     * $this->setMyField($value) === $this->setData('my_field', $value)
     * Uses cache to eliminate unnecessary preg_replace
     *
     * @param string $name
     *
     * @return string
     */
    protected function underscore(string $name): string
    {
        if (isset($this->underscoreCache[$name])) {
            return $this->underscoreCache[$name];
        }

        $result = strtolower(trim(preg_replace('/([A-Z]|[0-9]+)/', "_$1", $name), '_'));

        $this->underscoreCache[$name] = $result;

        return $result;
    }
}
