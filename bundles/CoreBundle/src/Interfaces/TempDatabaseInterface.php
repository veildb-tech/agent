<?php

declare(strict_types=1);

namespace DbManager\CoreBundle\Interfaces;

interface TempDatabaseInterface
{
    public function getName(): string;

    public function setName(string $name): TempDatabaseInterface;
}
