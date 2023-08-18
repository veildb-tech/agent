<?php

declare(strict_types=1);

namespace App\Service\Methods;

interface MethodInterface
{

    public function execute(array $dbConfig, string $dbUuid, ?string $filename = null): string;

}
