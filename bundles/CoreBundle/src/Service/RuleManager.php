<?php

declare(strict_types=1);

namespace DbManager\CoreBundle\Service;

use DbManager\CoreBundle\Interfaces\RuleManagerInteface;

class RuleManager implements RuleManagerInteface
{
    /**
     * @var array
     */
    private array $rules = [];

    /**
     * @param array $rules
     * @return RuleManagerInteface
     */
    public function set(array $rules): RuleManagerInteface
    {
        $this->rules = $rules;
        return $this;
    }

    /**
     * @return array
     */
    public function get(): array
    {
        return $this->rules;
    }
}
