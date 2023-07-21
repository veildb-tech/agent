<?php

declare(strict_types=1);

namespace App\ServiceApi\Entity;

use App\ServiceApi\AppService;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

final class Server extends AppService
{
    protected string $action = 'servers';

    /**
     * Get Server data
     *
     * @param string $uuid
     *
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws InvalidArgumentException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function get(string $uuid): array
    {
        $this->action = 'servers/' . $uuid;

        return $this->sendRequest([], 'GET');
    }

    /**
     * Create server data
     *
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
    public function create(array $data): array
    {
        return $this->sendRequest(
            [
                'json' => $data
            ]
        );
    }

    /**
     * Update server data
     *
     * @param string $uuid
     * @param array $data
     *
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws InvalidArgumentException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function update(string $uuid, array $data): array
    {
        $this->action = 'servers/' . $uuid;

        return $this->sendRequest(
            [
                'json' => $data
            ],
            'PATCH'
        );
    }
}
