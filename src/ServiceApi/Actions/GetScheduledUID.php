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
    /**
     * Get UID of scheduled DB
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
    public function get(): string
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
        return (string)$data['db']['uid'];
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
        $this->action = 'database_dumps?status=scheduled';

        return $this->sendRequest([], 'GET');
    }
}
