<?php

namespace tests;

use DbManager\TestBundle\Tests\AbstractTest;
use Doctrine\DBAL\Query\QueryBuilder;

class FakeTest extends AbstractTest
{
    public function testUsersTable(): void
    {
        $queryBuilder = new QueryBuilder($this->connection);
        $queryBuilder
            ->select('*')
            ->from('users')
            ->where("email NOT LIKE '%bridge.digital%'");

        $statement = $this->connection->executeQuery($queryBuilder->getSQL());

        // Fetch all rows from the result
        $rows = $statement->fetchAllAssociative();

        $this->assertNotEmpty($rows);

        $origin = $this->getOriginTableData('users');
        $origin = array_shift($origin);

        $this->assertNotEmpty($origin);
        $this->assertNotEmpty($origin['data']);

        foreach ($rows as $row) {
            $originRowIndex = array_search($row['id'], array_column($origin['data'], 'id'));
            $originRow = $origin['data'][$originRowIndex];
            foreach (['email', 'firstname', 'lastname', 'company'] as $column) {
                $this->assertNotEquals($originRow[$column], $row[$column]);
            }
        }
    }

    public function testConditionException(): void
    {
        $queryBuilder = new QueryBuilder($this->connection);
        $queryBuilder
            ->select('*')
            ->from('users')
            ->where("email LIKE '%bridge.digital%'")
            ->setParameter('origin', 'bridge.digital');

        $statement = $this->connection->executeQuery($queryBuilder->getSQL());

        // Fetch all rows from the result
        $rows = $statement->fetchAllAssociative();

        $this->assertCount(1, $rows);

        $row = array_shift($rows);
        $this->assertEquals('email@bridge.digital', $row['email']);
        $this->assertEquals('Bridge', $row['firstname']);
        $this->assertEquals('Digital', $row['lastname']);
        $this->assertEquals('Bridge Digital', $row['company']);
        $this->assertEquals('111-111-111', $row['telephone']);
    }
}
