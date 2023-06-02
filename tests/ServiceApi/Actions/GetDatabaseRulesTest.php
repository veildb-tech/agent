<?php

declare(strict_types=1);

namespace App\Tests;

use App\ServiceApi\Actions\GetDatabaseRules;
use App\ServiceApi\AppServiceClient;
use DbManager\CoreBundle\Service\RuleManager;
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
        $appServiceClient = $this->getMockBuilder(AppServiceClient::class)
            ->disableOriginalConstructor()
            ->getMock();

        $getDatabaseRules = new GetDatabaseRules($appServiceClient);

        $result = $getDatabaseRules->get('1');

        $this->assertInstanceOf(RuleManager::class, $result);

        $result = $result->getArrayCopy();
        $this->assertArrayHasKey('engine', $result);
        $this->assertArrayHasKey('rules', $result);
    }
}