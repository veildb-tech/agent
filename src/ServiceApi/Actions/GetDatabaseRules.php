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
     *
     * @return array [
     *      'engine' => 'mysql',
     *      'tables' => array[
     *          '<table>' => [
     *              '<column>' => [
     *                  <rules>
     *              ]
     *          ]
     *      ];
     * ]
     * @throws DecodingExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function get(string $databaseUid): array
    {
        // TODO: temporary returns fake data
        return [
            'engine' => 'mysql',
            'tables' => [
                'sales_order' => [
                    'method' => 'truncate',
                    'where' => 'customer_id != 66'
                ],
                'adminnotification_inbox' => [
                    'method' => 'truncate'
                ],
                'customer_entity' => [
                    'columns' => [
                        'email' => [
                            'method' => 'fake',
                            'value'  => 'test'
                        ]
                    ]
                ]
            ]
        ];
    }

    protected function getRules(string $databaseUid): string
    {
        return $this->sendRequest([
            'database' => $databaseUid
        ], 'GET');
    }
}
