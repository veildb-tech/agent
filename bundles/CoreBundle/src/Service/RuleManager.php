<?php

declare(strict_types=1);

namespace DbManager\CoreBundle\Service;

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
    private array $tables = [];

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
    public function setTables(array $tables): RuleManagerInterface
    {
        $this->tables = $tables;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getTables(): array
    {
       return $this->tables;
    }
}
