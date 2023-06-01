<?php

declare(strict_types=1);

namespace DbManager\MysqlBundle\Tests;

use DbManager\CoreBundle\DataProcessor\DataProcessorFactory;
use DbManager\MysqlBundle\Processor;
use Illuminate\Database\Connection;
use PHPUnit\Framework\TestCase;

class ProcessorTest extends TestCase
{
    /**
     * The test for getDbConnection function
     *
     * @param string $databaseName
     *
     * @dataProvider formGetDbConnection
     * @return void
     * @throws \ReflectionException
     */
    public function testGetDbConnection(string $databaseName)
    {
        $dataProcessorFactory = new DataProcessorFactory();
        $engineProcessor = new Processor($dataProcessorFactory);

        $result = $this->invokeMethod($engineProcessor, 'getDbConnection', [$databaseName]);

        $this->assertTrue($result instanceof Connection);
    }

    /**
     * Provider get
     *
     * @return array
     */
    public function formGetDbConnection(): array
    {
        return [
            'case_1' => [
                'databaseName' => 'db'
            ]
        ];
    }

    /**
     * Invoke private method
     *
     * @param $object
     * @param $methodName
     * @param array $parameters
     *
     * @return mixed
     * @throws \ReflectionException
     */
    protected function invokeMethod(&$object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}