<?php

declare(strict_types=1);

namespace App\Service\Methods;

use App\Exception\DumpNotFoundException;

class Manual extends AbstractMethod
{
    /**
     * @param array $dbConfig
     * @param string $dbUuid
     * @param string $filename
     * @return void
     * @throws DumpNotFoundException
     */
    public function execute(array $dbConfig, string $dbUuid, string $filename): void
    {
        $originFile = $this->getOriginFile($dbUuid, $dbConfig['dump_name']);
        $destFile = $this->getOriginFile($dbUuid, $filename);

        if (!is_file($originFile)) {
            throw new DumpNotFoundException("Dump file not found");
        }

        rename($originFile, $destFile);
    }
}
