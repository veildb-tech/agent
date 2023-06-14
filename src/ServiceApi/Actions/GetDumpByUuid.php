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

final class GetDumpByUuid extends AppService
{
    protected string $action = 'database_dumps';

    /**
     * @param string $dumpUuid
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
    public function execute(string $dumpUuid): array
    {
        $this->action .= '/' . $dumpUuid;
        return $this->sendRequest([], 'GET');
    }
}
