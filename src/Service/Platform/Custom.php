<?php

declare(strict_types=1);

namespace App\Service\Platform;

class Custom extends AbstractPlatform
{
    const CODE = 'custom';

    public function getCode(): string
    {
        return self::CODE;
    }

    public function getName(): string
    {
        return 'Custom';
    }

    /**
     * @param string $engine
     * @return bool
     */
    public function supports(string $engine): bool
    {
        return true;
    }
}
