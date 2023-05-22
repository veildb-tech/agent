<?php

declare(strict_types=1);

namespace DbManager\CoreBundle\Service;

use DbManager\CoreBundle\Interfaces\TempDatabaseInterface;

class TempDatabase implements TempDatabaseInterface
{
    private string $name = '';

    public function setName(string $name): TempDatabaseInterface
    {
        $this->name = $name;
        return $this;
    }
}
