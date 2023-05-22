<?php

declare(strict_types=1);

namespace DbManager\MysqlBundle\Service;

use DbManager\CoreBundle\Interfaces\EngineInterface;
use DbManager\CoreBundle\Interfaces\RuleManagerInteface;
use DbManager\CoreBundle\Interfaces\TempDatabaseInterface;

class EngineProcessor implements EngineInterface
{

    public string $test = '123';

    public function execute(RuleManagerInteface $rules, TempDatabaseInterface $tempDatabase)
    {
        exit('qwe');
        // TODO: Implement execute() method.
    }
}
