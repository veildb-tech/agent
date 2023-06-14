<?php

declare(strict_types=1);

namespace App\Tests\ServiceApi\Actions;

use App\ServiceApi\Actions\GetDatabaseRules;
use DG\BypassFinals;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class GetDatabaseRulesTest extends TestCase
{
    /**
     * The test for getDefaultRole function
     *
     * @return void
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
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
                'engine' => 'mysql',
                'rule' => [
                    'rule' => [
                        'sales_order' => [
                            'method' => 'truncate'
                        ]
                    ]
                ]
            ]
        );

        $result = $getDatabaseRules->get('1');

        $this->assertArrayHasKey('engine', $result);
        $this->assertArrayHasKey('rules', $result);
    }
}