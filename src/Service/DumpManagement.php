<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\File\File;
use App\ServiceApi\Actions\GetDumpByUuid;

class DumpManagement
{
    public function __construct(
        private readonly GetDumpByUuid $dumpWebService,
        private readonly string $dumpPath = ''
    ) {
    }

    /**
     * @param string $uuid
     * @return null | File
     * @throws \Exception
     */
    public function getDumpFileByUuid(string $uuid): ?File
    {
        $dump = $this->getDumpByUuid($uuid);

        if (empty($dump['filename'])) {
            return null;
        }

        if (empty($dump['db'])) {
            throw new \Exception("Couldn't allocate database for dump");
        }
        return new File($this->dumpPath . '/' . $dump['db']['uid']. '/' . $dump['filename']);
    }

    public function getDumpByUuid(string $uuid): array
    {
        return $this->dumpWebService->execute($uuid);
    }
}
