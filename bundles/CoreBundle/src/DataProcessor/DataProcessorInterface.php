<?php

declare(strict_types=1);

namespace DbManager\CoreBundle\DataProcessor;

use Exception;

interface DataProcessorInterface
{
    /**
     * @param string|null $column
     *
     * @return void
     * @throws Exception
     */
    public function truncate(?string $column = null): void;

    /**
     * Deletes table data with provided condition
     *
     * @param string      $condition
     * @param string|null $column
     *
     * @throws Exception
     */
    public function delete(string $condition, ?string $column = null): void;

    /**
     * Update values
     *
     * @param string      $field
     * @param string      $value
     * @param string|null $condition
     *
     * @return void
     * @throws Exception
     */
    public function update(string $field, string $value, ?string $condition = null): void;
}