<?php

declare(strict_types=1);

namespace App\Service\Methods;

use App\Enum\MethodsEnum;
use App\Exception\NoSuchMethodException;

class MethodFactory
{
    /**
     * TODO: replace by service
     *
     * @param Manual $manual
     * @param Dump $dump
     */
    public function __construct(
        private readonly Manual $manual,
        private readonly Dump $dump
    ) {
    }

    /**
     * TODO: implement return of services via container instead of construct
     *
     * @param string $method
     * @return MethodInterface
     * @throws NoSuchMethodException
     */
    public function create(string $method): MethodInterface
    {
        return match ($method) {
            MethodsEnum::MANUAL->value => $this->manual,
            MethodsEnum::DUMP->value => $this->dump,
            default => throw new NoSuchMethodException(sprintf("Method %s is not exists", $method)),
        };
    }
}
