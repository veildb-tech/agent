<?php

namespace App\Service\Actions;

interface ActionInteface
{
    public function getMethod(): string;
    public function getUri(): string;

    public function getQuery(): array;
}
