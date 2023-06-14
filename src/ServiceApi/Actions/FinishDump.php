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
   public const DUMP_ID_IRI = '/api/database_dumps/';

    protected string $action = 'database_dumps';

    /**
     * @param string $dumpUuid
     * @param string $filename
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
    public function execute(string $dumpUuid, string $filename): void
    {
        $this->action  .= '/' . $dumpUuid;
        $this->sendRequest([
            'json' => [
                'filename' => $filename,
                'status' => 'ready'
            ]
        ], 'PATCH');
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
