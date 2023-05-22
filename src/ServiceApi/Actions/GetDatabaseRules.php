<?php

namespace App\ServiceApi\Actions;

use App\ServiceApi\AppService;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class GetDatabaseRules extends AppService
{
    /**
     * @var string
     */
    protected string $action = 'rules';

    /**
     * @param string $databaseUid
     * @return array [
     *  'engine' => 'mysql',
     *  'rules' => array[];
     * ]
     * @throws DecodingExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function get(string $databaseUid): array
    {
        // TODO: temporary returns fake data
        return [
            'engine' => 'mysql',
            'rules' => []
        ];
        return $this->sendRequest([
            'database' => $databaseUid
        ], 'GET');
    }
}
