<?php

declare(strict_types=1);

namespace App\Service;

use App\ServiceApi\Entity\DatabaseDump;
use Exception;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class DumpManagement
{
    public function __construct(
        private readonly DatabaseDump $databaseDump,
        private readonly string $dumpPath = ''
    ) {
    }

    /**
     * @param string $uuid
     * @return File|null
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws InvalidArgumentException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    public function getDumpFileByUuid(string $uuid): ?File
    {
        $dump = $this->getDumpByUuid($uuid);

        if (empty($dump['filename'])) {
            return null;
        }

        if (empty($dump['db'])) {
            throw new Exception("Couldn't allocate database for dump");
        }
        return new File($this->dumpPath . '/' . $dump['db']['uid'] . '/' . $dump['filename']);
    }

    /**
     * @param string $uuid
     *
     * @return array
     * @throws InvalidArgumentException
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getDumpByUuid(string $uuid): array
    {
        return $this->databaseDump->getByUuid($uuid);
    }
}
