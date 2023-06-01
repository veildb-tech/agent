<?php

declare(strict_types=1);

namespace App\Tests;

use App\ServiceApi\Actions\GetDatabaseRules;
use App\ServiceApi\AppServiceClient;
use PHPUnit\Framework\TestCase;

class GetDatabaseRulesTest extends TestCase
{
    /**
     * The test for getDefaultRole function
     *
     * @param string $databaseUid
     * @param array $expected
     *
     * @dataProvider formGetProvider
     * @return void
     */
    public function testGet(string $databaseUid, array $expected)
    {
        $appServiceClient = $this->getMockBuilder(AppServiceClient::class)
            ->disableOriginalConstructor()
            ->getMock();

        $getDatabaseRules = new GetDatabaseRules($appServiceClient);

        $result = $getDatabaseRules->get($databaseUid);
        $this->assertSame($result, $expected);

        $this->assertArrayHasKey('engine', $result);
        $this->assertArrayHasKey('tables', $result);
    }

    /**
     * Provider get
     *
     * @return array
     */
    public function formGetProvider(): array
    {
        return [
            'case_1' => [
                'databaseUid' => '1',
                'expected' => [
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
                ],
            ]
        ];
    }
}