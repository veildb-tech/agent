<?php

declare(strict_types=1);

namespace App\Service\Methods;

use App\Service\InputOutput;

interface MethodInterface
{
    /**
     * Use code to specify method in configurations
     *
     * @return string
     */
    public function getCode(): string;

    /**
     * Retrieve description of method
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Ask for configuration via CLI
     *
     * @param InputOutput $inputOutput
     * @param array $config
     *
     * @return array
     */
    public function askConfig(InputOutput $inputOutput, array $config = []): array;

    /**
     * Method which process backup
     *
     * @param array $dbConfig
     * @param string $dbUuid
     * @param string|null $filename
     * @return string
     */
    public function execute(array $dbConfig, string $dbUuid, ?string $filename = null): string;

    /**
     * Check if method supports provided engine
     *
     * @param string $engine
     * @return bool
     */
    public function support(string $engine): bool;

    /**
     * Check if connection is OK
     *
     * @param array $config
     *
     * @return bool
     */
    public function validate(array $config): bool;
}
