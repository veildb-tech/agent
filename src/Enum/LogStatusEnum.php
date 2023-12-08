<?php

declare(strict_types=1);

namespace App\Enum;

enum LogStatusEnum: string
{
    case SUCCESS = 'success';
    case ERROR = 'error';
    case PROCESSING = 'processing';
}
