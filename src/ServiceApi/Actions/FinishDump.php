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

final class FinishDump extends AppService
{
    protected string $action = 'database_dumps';

    /**
     * @param string $dumpUuid
     * @param string $status
     * @param string|null $filename
     *
     * @return void
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function execute(string $dumpUuid, string $status, ?string $filename = ''): void
    {
        $this->action  .= '/' . $dumpUuid;
        $this->sendRequest([
            'json' => [
                'filename' => $filename,
                'status' => $status
            ]
        ], 'PATCH');
    }
}
