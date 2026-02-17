<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin     = 'admin';
    case Organizer = 'organizer';
    case Customer  = 'customer';

    public function label(): string
    {
        return match($this) {
            self::Admin     => 'Administrator',
            self::Organizer => 'Event Organizer',
            self::Customer  => 'Customer',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
