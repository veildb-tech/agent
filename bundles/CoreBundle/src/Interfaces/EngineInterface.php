<?php

namespace DbManager\CoreBundle\Interfaces;

interface EngineInterface
{
    public function execute(RuleManagerInteface $rules, TempDatabaseInterface $tempDatabase);
}
