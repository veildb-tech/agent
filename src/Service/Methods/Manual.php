<?php

declare(strict_types=1);

namespace App\Service\Methods;

use App\Exception\DumpNotFoundException;

class Manual extends AbstractMethod
{

    /**
     * @param array $dbConfig
     * @param string $dbUuid
     * @param string|null $filename
     * @return string
     * @throws DumpNotFoundException
     */
    public function execute(array $dbConfig, string $dbUuid, ?string $filename = null): string
    {
        $originFile = $this->getOriginFile($dbUuid, $dbConfig['dump_name']);

        if (!$filename) {
            $filename = time() . '.sql';
        }
        $destFile = $this->getOriginFile($dbUuid, $filename);

        if (!is_file($originFile)) {
            throw new DumpNotFoundException("Dump file not found");
        }

        rename($originFile, $destFile);
        return $destFile;

    }
}
