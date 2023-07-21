<?php

declare(strict_types=1);

namespace App\ServiceApi;

use App\Service\AppConfig;
use Exception;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class AppService
{
    private const AUTH_TYPE_USER = 'User';
    private const AUTH_TYPE_TOKEN = 'Token';

    /**
     * @var string
     */
    protected string $method = 'POST';

    /**
     * @var string
     */
    protected string $action;

    /**
     * @var string|null
     */
    protected null|string $user = null;

    /**
     * @var string|null
     */
    protected null|string $pass = null;

    /**
     * @param AppConfig $appConfig
     * @param AppServiceClient $client
     * @param CacheInterface $cacheAdapter
     */
    public function __construct(
        private readonly AppConfig $appConfig,
        private readonly AppServiceClient $client,
        private readonly CacheInterface $cacheAdapter
    ) {
    }

    /**
     * Set access credentials
     *
     * @param string $user
     * @param string $passwd
     *
     * @return AppService
     */
    public function setCredentials(string $user, string $passwd): self
    {
        $this->user = $user;
        $this->pass = $passwd;

        return $this;
    }

    /**
     * @param array $params
     * @param string $method
     *
     * @return array
     *
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException|DecodingExceptionInterface
     */
    public function sendRequest(array $params, string $method = 'POST'): array
    {
        if (!$this->action) {
            throw new Exception("Action is required");
        }
        $this->method = $method;

        $options  = $this->getOptions($params);
        $response = $this->getClient()->request($method, $this->action, $options);

        return $response->toArray();
    }

    /**
     * @return AppServiceClient
     */
    public function getClient(): AppServiceClient
    {
        return $this->client;
    }

    /**
     * Prepare options to send
     *
     * @param array $params
     *
     * @return array
     * @throws InvalidArgumentException
     */
    protected function getOptions(array $params): array
    {
        $options = [
            'headers' => $this->getHeaders()
        ];
        return array_merge($options, $params);
    }

    /**
     * Getting Access token
     *
     * @return string
     * @throws InvalidArgumentException
     * @throws Exception
     */
    protected function getSecurityToken(): string
    {
        $authType = $this->getAuthorizationType();
        $key = 'apg-token-' . $authType;

        $token = $this->cacheAdapter->get($key, function (ItemInterface $item) use ($authType) {
            if (!$item->isHit()) {
                switch ($authType) {
                    case self::AUTH_TYPE_TOKEN:
                        $response = $this->getServerToken();
                        break;
                    case self::AUTH_TYPE_USER:
                        $response = $this->getUserAccessToken();
                        break;
                }

                $item->expiresAfter(3600);
                $item->set($response);
            }
            return $item->get();
        });

        if (!$token) {
            throw new Exception('You have not access to do the operation');
        }
        return $token;
    }

    /**
     * Get User Access token
     *
     * @return string
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    protected function getUserAccessToken(): string
    {
        $response = $this->getClient()->request(
            'POST',
            'login_check',
            [
                'headers' => [
                    'Accept'       => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'username' => $this->user,
                    'password' => $this->pass
                ]
            ]
        );

        $result = $response->toArray();

        return $result['token'];
    }

    /**
     * Get server token
     *
     * @return string
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    protected function getServerToken(): string
    {
        $response = $this->getClient()->request(
            'POST',
            'token_check',
            [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'uuid'       => $this->appConfig->getServerUuid(),
                    'secret_key' => $this->appConfig->getServerSecretKey(),
                ]
            ]
        );

        $result = $response->toArray();

        return $result['token'];
    }

    /**
     * Retrieve headers
     *
     * @return array
     * @throws InvalidArgumentException
     */
    protected function getHeaders(): array
    {
        return [
            'Accept'       => 'application/json',
            'Content-Type' => $this->method === 'PATCH' ? 'application/merge-patch+json' : 'application/json',
            'Authorization-Type' => $this->getAuthorizationType(),
            'Authorization'      => sprintf('Bearer %s', $this->getSecurityToken())
        ];
    }

    /**
     * Get authorization type
     *
     * @return string
     */
    private function getAuthorizationType(): string
    {
        if ($this->pass && $this->user) {
            return self::AUTH_TYPE_USER;
        }
        return self::AUTH_TYPE_TOKEN;
    }
}
