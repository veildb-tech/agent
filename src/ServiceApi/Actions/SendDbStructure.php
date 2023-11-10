<?php

declare(strict_types=1);

namespace App\ServiceApi\Actions;

use App\ServiceApi\AppService;
use Illuminate\Database\Eloquent\Casts\Json;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

final class SendDbStructure extends AppService
{
    protected string $action = 'databases';

    /**
     * Set DB structure
     *
     * @param string $dbUid
     * @param array $data
     *
     * @return void
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws InvalidArgumentException
     */
    public function execute(string $dbUid, array $data): void
    {
        $this->action .= '/' . $dbUid;

        $this->sendData(
            JSON::encode($data['db_schema']),
            JSON::encode($data['additional_data'] ?? []),
        );
    }

    /**
     * @param string $dbSchema
     * @param string $additionalData
     *
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws InvalidArgumentException
     */
    protected function sendData(string $dbSchema, string $additionalData): array
    {
        return $this->sendRequest(
            [
                'json' => [
                    'status'         => 'enabled',
                    'dbSchema'       => $dbSchema,
                    'additionalData' => $additionalData
                ]
            ],
            'PATCH'
        );
    }
}
