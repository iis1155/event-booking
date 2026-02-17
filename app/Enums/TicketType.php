<?php

namespace App\Enums;

enum TicketType: string
{
    case VIP       = 'VIP';
    case Standard  = 'Standard';
    case Economy   = 'Economy';
    case EarlyBird = 'Early Bird';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
