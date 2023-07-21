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

final class AddDatabase extends AppService
{
    protected string $action = 'databases';

    /**
     * @param array $data
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws InvalidArgumentException
     */
    public function execute(array $data): array
    {
        return $this->sendRequest(['json' => $data]);
    }
}
