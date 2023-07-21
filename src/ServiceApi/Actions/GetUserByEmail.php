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

final class GetUserByEmail extends AppService
{
    protected string $action = 'users';

    /**
     * Get user data
     *
     * @param string $email
     *
     * @return array
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function execute(string $email): array
    {
        $result = $this->getUserByEmail($email);

        if (!count($result)) {
            throw new Exception(sprintf('There user with email %s was not found', $email));
        }
        return current($result);
    }

    /**
     * @param string $email
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
    protected function getUserByEmail(string $email): array
    {
        return $this->sendRequest(
            [
                'query' => [
                    'email' => $email
                ]
            ],
            'GET'
        );
    }
}
