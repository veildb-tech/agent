<?php

declare(strict_types=1);

namespace App\ServiceApi\Actions;

use App\ServiceApi\AppService;
use DbManager\CoreBundle\Service\RuleManager;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

final class GetDatabaseRules extends AppService
{
    /**
     * @param string $databaseUid
     *
     * @return RuleManager ([
     *      'engine' => 'mysql',
     *      'tables' => array[
     *          '<table>' => [
     *              '<column>' => [
     *                  <rules>
     *              ]
     *          ]
     *      ];
     * ])
     */
    public function get(string $databaseUid): RuleManager
    {
        $result = $this->getRules($databaseUid);

        return new RuleManager([
            'engine' => $result['engine'],
            'rules'  => $result['databaseRules']['rule']
        ]);

        return new RuleManager([
            'engine' => 'mysql',
            'rules'  => [
                'sales_order' => [
                    'method' => 'truncate',
                    'where' => 'entity_id != 67',
                ],
                'adminnotification_inbox' => [
                    'method' => 'truncate',
                ],
                'admin_user' => [
                    'method' => 'truncate|fake',
                    'fake_data' => [
                        'email' => 'admin@gmail.com',
                        'username' => 'admin',
                        'password' => 'admin'
                    ]
                ],
                'customer_entity' => [
                    'columns' => [
//                        'email' => [
//                            'method' => 'update',
//                            'value'  => "CONCAT ('test_', email)",
//                            'where'  => "email NOT LIKE ('%@overdose.digital')",
//                        ],
                        'email' => [
                            'method' => 'fake',
                            'where'  => "email NOT LIKE ('%@overdose.digital')",
                        ],
                        'firstname' => [
                            'method' => 'update',
                            'value'  => 'null',
                            'where'  => "created_in LIKE '%NZ Store%' OR lastname = 'Miles'",
                        ],
                    ],
                ],
                'customer_entity_varchar' => [
                    'columns' => [
                        'value' => [
                            'method' => 'update',
                            'value'  => "md5('Admin123')",
                            'where'  => "attribute_id IN (SELECT attribute_id FROM eav_attribute WHERE attribute_code = 'password_hash' AND entity_type_id = 1)"
                        ],
                    ],
                ],
            ],
        ]);
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
     */
    protected function getRules(string $databaseUid): array
    {
        $this->action = 'databases/' . $databaseUid;

        return $this->sendRequest([], 'GET');
    }
}
