<?php

declare(strict_types=1);

namespace DbManager\CoreBundle\DataProcessor;

interface DataProcessorInterface
{
    /**
     * @return void
     */
    public function truncate(): void;

    /**
     * Deletes table data with provided condition
     *
     * @param string $condition
     */
    public function delete(string $condition): void;

    /**
     * Update values
     *
     * @param string      $field
     * @param string      $value
     * @param string|null $condition
     *
     * @return void
     */
    public function update(string $field, string $value, ?string $condition = null): void;
}