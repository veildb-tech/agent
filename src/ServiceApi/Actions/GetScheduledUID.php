<?php

declare(strict_types=1);

namespace App\ServiceApi\Actions;

use App\ServiceApi\AppService;
use Exception;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

final class GetScheduledUID extends AppService
{
    protected string $action = 'database_dumps';

    /**
     * Get UID of scheduled DB
     * Command will return data in format: <backup Uuid>:<db Uid>
     *
     * @return string
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    public function execute(): string
    {
        $result = $this->getScheduledDB();

        return $this->formUIDData($result);
    }

    /**
     * @param array $data
     *
     * @return string
     *
     * @throws Exception
     */
    protected function formUIDData(array $data): string
    {
        if (!count($data)) {
            throw new Exception('There are no scheduled Databases');
        }
        $data = current($data);

        if (!isset($data['db']['uid'])) {
            throw new Exception('An information about DB was not found...');
        }

        return $data['uuid'] . ':' . $data['db']['uid'];
    }

    /**
     * @return array
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    protected function getScheduledDB(): array
    {
        return $this->sendRequest(
            [
                'query' => [
                    'status' => 'scheduled'
                ]
            ],
            'GET'
        );
    }
}
