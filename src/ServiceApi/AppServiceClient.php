<?php

declare(strict_types=1);

namespace App\ServiceApi;

use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class AppServiceClient
{
    /**
     * @var HttpClientInterface
     */
    protected HttpClientInterface $client;

    /**
     * @param HttpClientInterface $httpClient
     * @param string              $serviceUrl
     */
    public function __construct(
        protected HttpClientInterface $httpClient,
        protected string $serviceUrl = ''
    ) {
    }

    /**
     * @param string $method
     * @param string $action
     * @param array  $options
     *
     * @return ResponseInterface
     *
     * @throws TransportExceptionInterface
     */
    public function request(string $method, string $action, array $options = []): ResponseInterface
    {
        return $this->httpClient->request($method, $this->getUrl($action), $options);
    }

    /**
     * @param string $action
     *
     * @return string
     */
    protected function getUrl(string $action): string
    {
        return rtrim($this->serviceUrl, '/') . '/api/' . $action;
    }
}
