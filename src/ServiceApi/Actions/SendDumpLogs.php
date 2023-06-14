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

final class SendDumpLogs extends AppService
{
    public const DUMP_ID_IRI = '/api/database_dumps/';

    /**
     * Get UID of scheduled DB
     * Command will return data in format: <backup Uuid>:<db Uid>
     *
     * @param string $dumpUuid
     * @param string $status
     * @param string $message
     *
     * @return void
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    public function execute(string $dumpUuid, string $status, string $message): void
    {
        $log = [
            'status'  => $status,
            'message' => $message,
            'dumpId'  => self::DUMP_ID_IRI . $dumpUuid,
        ];

        $this->sendLogs($log);
    }

    /**
     * @param array $log
     *
     * @return array
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    protected function sendLogs(array $log): array
    {
        $this->action = 'database_dump_logs';

        return $this->sendRequest($log, 'POST');
    }
}
