<?php

declare(strict_types=1);

namespace App\Service\Engine;

interface EngineInterface
{
    public function getCode(): string;
    public function getName(): string;
}