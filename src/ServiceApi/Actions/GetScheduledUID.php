<?php

declare(strict_types=1);

namespace App\ServiceApi\Actions;

use App\ServiceApi\AppService;
use Exception;
use Psr\Cache\InvalidArgumentException;
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
     * @return array
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    public function execute(): array
    {
        $result = $this->getScheduledDB();

        return $this->formUIDData($result);
    }

    /**
     * @param array $data
     *
     * @return array
     *
     * @throws Exception
     */
    protected function formUIDData(array $data): array
    {
        if (!count($data)) {
            throw new Exception('There are no scheduled Databases');
        }
        $data = current($data);

        if (!isset($data['db']['uid'])) {
            throw new Exception('An information about DB was not found...');
        }

        return $data;
    }

    /**
     * @return array
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws InvalidArgumentException
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
