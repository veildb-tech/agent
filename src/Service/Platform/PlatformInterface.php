<?php

declare(strict_types=1);

namespace App\Service\Platform;

interface PlatformInterface
{
    public function getCode(): string;
    public function getName(): string;
    public function supports(string $engine): bool;
}
