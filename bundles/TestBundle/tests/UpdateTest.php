<?php

namespace tests;

use DbManager\TestBundle\Tests\AbstractTest;
use Doctrine\DBAL\Query\QueryBuilder;

class UpdateTest extends AbstractTest
{
    public function testOrderTable(): void
    {
        $queryBuilder = new QueryBuilder($this->connection);
        $queryBuilder
            ->select('*')
            ->from('orders');

        $statement = $this->connection->executeQuery($queryBuilder->getSQL());

        // Fetch all rows from the result
        $rows = $statement->fetchAllAssociative();
        $this->assertNotEmpty($rows);

        foreach ($rows as $row) {
            $this->assertEquals('updated', $row['shipping_address']);
        }
    }
}
