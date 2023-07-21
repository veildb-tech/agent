<?php

declare(strict_types=1);

namespace App\Enum;

enum ServerStatusEnum: string
{
    case ENABLED = 'enabled';
    case DISABLED = 'disabled';
    case PENDING = 'pending';

    /**
     * Get Values list
     *
     * @return array
     */
    public static function getValues(): array
    {
        return array_column(ServerStatusEnum::cases(), 'value');
    }
}
