<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
class AppService
{

    //todo: change
    private string $serviceUrl = 'http://host.docker.internal/api/';
    private HttpClientInterface $client;

    public function __construct(
        HttpClientInterface $client
    )
    {
        $this->client = $client;
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendRequest(array $data): array
    {
        $response = $this->client->request($data['method'], $this->getUrl($data['uri']), $this->getOptions($data));

        return $response->toArray();
    }

    public function getScheduledDump(): string
    {

    }

    /**
     * Prepare options to send
     *
     * @param array $data
     * @return array
     */
    private function getOptions(array $data): array
    {
        return [
            'body' => $data['body'],
            'headers' => [
                'Accept' => 'application/json'
            ]
        ];
    }

    /**
     * Retrieve url to API
     *
     * @param string $uri
     * @return string
     */
    private function getUrl(string $uri): string
    {
        return $this->serviceUrl . $uri;
    }

    /*    public function getRequest(): array | bool
        {
            return [
                [
                    'project' => 'ka81nasf',
                    'provider' => 'dump',
                    'engine' => 'mysql',
                    'rule_id' => 'ewgrib71ja',
                ]
            ];
        }

        public function proceedRequest(array $request): bool
        {
            return true;

        }
    }*/
}
