<?php

declare(strict_types=1);

namespace App\ServiceApi\Entity;

use App\ServiceApi\AppService;
use Exception;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

final class Database extends AppService
{
    /**
     * Api URL
     */
    public const ACTION_URL = 'databases';

    /**
     * @param array $data
     *
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws InvalidArgumentException
     */
    public function add(array $data): array
    {
        $this->action = self::ACTION_URL;

        return $this->sendRequest(['json' => $data]);
    }

    /**
     * Get list of databases
     *
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws InvalidArgumentException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getList(): array
    {
        $this->action = self::ACTION_URL;

        return $this->sendRequest([], 'GET');
    }

    /**
     * @param string $dumpUuid
     *
     * @return array
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws InvalidArgumentException
     */
    public function getByUuid(string $dumpUuid): array
    {
        $this->action = self::ACTION_URL . '/' . $dumpUuid;

        return $this->sendRequest([], 'GET');
    }

    /**
     * @param string $dbUuid
     * @param array $data
     *
     * @return void
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws InvalidArgumentException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function update(string $dbUuid, array $data): void
    {
        $this->action = self::ACTION_URL . '/' . $dbUuid;

        $this->sendRequest(
            [
                'json' => $data
            ],
            'PATCH'
        );
    }

    /**
     * @param string $dumpUuid
     *
     * @return void
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws InvalidArgumentException
     */
    public function delete(string $dumpUuid): void
    {
        $this->action = self::ACTION_URL . '/' . $dumpUuid;

        $this->sendDeleteRequest([]);
    }
}
