<?php

declare(strict_types=1);

namespace App\ServiceApi\Actions;

use App\ServiceApi\AppService;
use Illuminate\Database\Eloquent\Casts\Json;
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
     * @param array $structure
     *
     * @return void
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function execute(string $dbUid, array $structure): void
    {
        $this->action .= '/' . $dbUid;

        $this->sendData(
            JSON::encode($structure['db_schema']),
            JSON::encode($structure['additional_data']),
        );
    }

    /**
     * @param string $dbSchema
     * @param string $additionalData
     *
     * @return array
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    protected function sendData(string $dbSchema, string $additionalData): array
    {
        return $this->sendRequest(
            [
                'json' => [
                    'dbSchema' => $dbSchema,
                    'additionalData' => $additionalData
                ]
            ],
            'PATCH'
        );
    }

    /**
     * @inheritDoc
     */
    protected function getHeaders(): array
    {
        $headers = parent::getHeaders();
        $headers['Content-Type'] = 'application/merge-patch+json';

        return $headers;
    }
}
