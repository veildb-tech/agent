<?php

declare(strict_types=1);

namespace App\Tests;

use App\ServiceApi\Actions\GetDatabaseRules;
use DbManager\CoreBundle\Service\RuleManager;
use DG\BypassFinals;
use PHPUnit\Framework\TestCase;

class GetDatabaseRulesTest extends TestCase
{
    /**
     * The test for getDefaultRole function
     *
     * @return void
     */
    public function testGet()
    {
        BypassFinals::enable();

        $getDatabaseRules = $this->getMockBuilder(GetDatabaseRules::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRules'])
            ->getMock();

        $getDatabaseRules->method(
            'getRules'
        )->with(
            '1'
        )->willReturn(
            [
                'id' => 1,
                'engine_id' => [
                    'code' => 'mysql'
                ],
                'databaseRules' => [
                    'rule' => [
                        'sales_order' => [
                            'method' => 'truncate'
                        ]
                    ]
                ]
            ]
        );

        $result = $getDatabaseRules->get('1');

        $this->assertInstanceOf(RuleManager::class, $result);

        $result = $result->getArrayCopy();
        $this->assertArrayHasKey('engine', $result);
        $this->assertArrayHasKey('rules', $result);
    }
}