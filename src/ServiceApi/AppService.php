<?php

declare(strict_types=1);

namespace App\ServiceApi;

use Exception;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class AppService
{
    /**
     * @var string
     */
    protected string $action;

    /**
     * @param AppServiceClient $client
     */
    public function __construct(private readonly AppServiceClient $client)
    {
    }

    /**
     * @param array  $params
     * @param string $method
     *
     * @return array
     *
     * @throws DecodingExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws Exception
     */
    public function sendRequest(array $params, string $method = 'POST'): array
    {
        if (!$this->action) {
            throw new Exception("Action is required");
        }

        $options = $this->getOptions($params);
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
     */
    protected function getOptions(array $params): array
    {
        $options = [
            'headers' => $this->getHeaders()
        ];

        return array_merge($options, $params);
    }

    /**
     * Retrieve headers
     *
     * @return string[]
     */
    protected function getHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ];
    }
}
