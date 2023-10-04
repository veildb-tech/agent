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

final class GetDatabaseRules extends AppService
{
    /**
     * Get array with rules data
     *
     * Expected return value is: [
     *      'engine' => 'mysql',
     *      'tables' => array[
     *          '<table>' => [
     *              '<column>' => [
     *                  <rules>
     *              ]
     *          ]
     *      ];
     * ]
     *
     * @param string $databaseUid
     *
     * @return array
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function get(string $databaseUid): array
    {
        $result = $this->getRules($databaseUid);
        return [
            'engine'    => $this->formEngineData($result),
            'platform'  => $this->formPlatformData($result),
            'rules'     => $this->formRulesData($result),
        ];
    }

    /**
     * @param array $data
     *
     * @return string
     * @throws Exception
     */
    protected function formEngineData(array $data): string
    {
        if (!isset($data['engine'])) {
            throw new Exception('An information about DB engine was not found...');
        }
        return (string)$data['engine'];
    }

    /**
     * @param array $data
     *
     * @return array
     * @throws Exception
     */
    protected function formRulesData(array $data): array
    {
        if (!isset($data['databaseRule'])) {
            throw new Exception('An information about DB processing rules was not found...');
        }

        return $this->prepareRules($data['databaseRule']['rule']);
    }

    /**
     * @param array $data
     *
     * @return string
     * @throws Exception
     */
    protected function formPlatformData(array $data): string
    {
        return $data['platform'] ?? 'custom';
    }

    /**
     * @param string $databaseUid
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
    protected function getRules(string $databaseUid): array
    {
        $this->action = 'databases/' . $databaseUid;

        return $this->sendRequest([], 'GET');
    }

    /**
     * Prepare rules
     *
     * @param array $rules
     * @return array
     */
    private function prepareRules(array $rules): array
    {
        // Nothing to do here. Rules should be delivered prepared :)
        return $rules;
    }
}
