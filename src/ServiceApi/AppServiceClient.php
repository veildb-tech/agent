<?php

namespace App\ServiceApi;

use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class AppServiceClient
{
    const SERVICE_URL = 'https://service.url/';

    /**
     * @var HttpClientInterface
     */
    protected HttpClientInterface $client;

    /**
     * @param HttpClientInterface $httpClient
     * @param string $workspace
     * @param string $apiToken
     * @param string $serviceUrl
     */
    public function __construct(
        protected HttpClientInterface $httpClient,
        protected string $workspace,
        protected string $apiToken,
        protected string $serviceUrl = self::SERVICE_URL
    )
    {
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function request(string $method, string $action, array $options = []): ResponseInterface
    {
        return $this->httpClient->request($method, $this->getUrl($action), $options);
    }

    /**
     * @param string $action
     * @return string
     */
    protected function getUrl(string $action): string
    {
        return $this->serviceUrl . $action;
    }
}
