<?php

namespace DbManager\TestBundle\Tests;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\DBAL\DriverManager;

abstract class AbstractTest extends KernelTestCase
{
    protected ?Connection $connection;

    protected array $originData = [];

    protected function setUp(): void
    {
        // Boot the Symfony kernel for access to the container
        self::bootKernel();

        // Get the Doctrine DBAL connection
        $this->connection = $this->createConnection();
    }

    protected function tearDown(): void
    {
        // Close the database connection after each test
        $this->connection?->close();
        parent::tearDown();
    }

    private function createConnection(): Connection
    {
        // Replace with your actual database connection parameters
        $dbParams = [
            'driver' => 'pdo_mysql',
            'host' => $_ENV['DATABASE_HOST'],
            'port' => $_ENV['DATABASE_PORT'],
            'dbname' => $_ENV['DATABASE_NAME'],
            'user' => $_ENV['DATABASE_USER'],
            'password' => $_ENV['DATABASE_PASSWD'],
        ];

        return DriverManager::getConnection($dbParams);
    }

    protected function getOriginalData(): array
    {
        if (empty($this->originData)) {
            $path = self::$kernel->getBundle('DbManagerTestBundle')->getPath();
            $jsonFilePath = $path . '/data/origin.json';

            if (file_exists($jsonFilePath)) {
                $jsonContent = file_get_contents($jsonFilePath);
                $this->originData = json_decode($jsonContent, true);
            } else {
                $this->fail("The JSON file does not exist: $jsonFilePath");
            }
        }

        return $this->originData;
    }

    protected function getOriginTableData(string $tableName): array
    {
        $originalData = $this->getOriginalData();

        return array_filter($originalData, function ($element) use ($tableName) {
            return $element['type'] === 'table' && $element['name'] === $tableName;
        });
    }
}
