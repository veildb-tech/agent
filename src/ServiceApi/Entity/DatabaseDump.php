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

final class DatabaseDump extends AppService
{
    /**
     * Api URL
     */
    public const ACTION_URL = 'database_dumps';

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
     * @throws InvalidArgumentException
     */
    public function getScheduled(): array
    {
        $this->action = self::ACTION_URL;

        $result = $this->sendRequest(
            [
                'query' => [
                    'status' => 'scheduled'
                ]
            ],
            'GET'
        );

        return $this->formUIDData($result);
    }

    /**
     * @param string $dumpUuid
     * @param string $status
     * @param string|null $filename
     *
     * @return void
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws InvalidArgumentException
     */
    public function updateByUuid(string $dumpUuid, string $status, ?string $filename = ''): void
    {
        $this->action = self::ACTION_URL . '/' . $dumpUuid;

        $this->sendRequest(
            [
                'json' => [
                    'filename' => $filename,
                    'status' => $status
                ]
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
}
