<?php

namespace App\Enums;

enum TripStatus: string
{
    case Planned = 'planned';
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /**
     * Options for Filament Select fields.
     */
    public static function options(): array
    {
        return [
            self::Planned->value   => 'Planned',
            self::Active->value    => 'Active',
            self::Completed->value => 'Completed',
            self::Cancelled->value => 'Cancelled',
        ];
    }

    /**
     * Colors for Filament badge columns.
     */
    public static function colors(): array
    {
        return [
            'primary' => self::Planned->value,
            'warning' => self::Active->value,
            'success' => self::Completed->value,
            'danger'  => self::Cancelled->value,
        ];
    }
}
