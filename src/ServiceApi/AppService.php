<?php

declare(strict_types=1);

namespace App\ServiceApi;

use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AppService
{
    /**
     * @var string
     */
    protected string $action = '';

    /**
     * @param AppServiceClient $client
     */
    public function __construct(
        private AppServiceClient $client
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws \Exception
     * @throws DecodingExceptionInterface
     */
    public function sendRequest(array $params, $method = 'POST'): array
    {
        if (empty($this->action)) {
            throw new \Exception("Action is required");
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
     * @param array $data
     * @return array
     */
    protected function getOptions(array $params): array
    {
        return [
            'body' => $params,
            'headers' => [
                'Accept' => 'application/json'
            ]
        ];
    }
}
