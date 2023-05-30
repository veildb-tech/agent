<?php

declare(strict_types=1);

namespace DbManager\CoreBundle\Service;

use DbManager\CoreBundle\Interfaces\TempDatabaseInterface;

class TempDatabase implements TempDatabaseInterface
{
    private string $name = '';

    /**
     * Get DB name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set DB Name
     *
     * @param string $name
     *
     * @return TempDatabaseInterface
     */
    public function setName(string $name): TempDatabaseInterface
    {
        $this->name = $name;

        return $this;
    }
}
