<?php

declare(strict_types=1);

namespace App\ServiceApi\Actions;

use App\Exception\AccessDenyException;
use App\ServiceApi\AppService;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

final class ValidateAccessToken extends AppService
{
    protected string $action = 'validate_token';

    /**
     * Validate access token
     *
     * @param string $token
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws InvalidArgumentException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws AccessDenyException
     */
    public function execute(string $token): void
    {
        $this->action .= '/' . $token . '/';

        $data = $this->sendRequest([], 'GET');

        if (!count($data) || !(bool)$data['result']) {
            throw new AccessDenyException('Access denied');
        }
    }
}
