<?php

namespace App\Enums;

enum TripStatus: string
{
    case PLANNED = 'planned';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    /**
     * Options for Filament Select fields.
     */
    public static function options(): array
    {
        return [
            self::PLANNED->value   => 'Planned',
            self::ACTIVE->value    => 'Active',
            self::COMPLETED->value => 'Completed',
            self::CANCELLED->value => 'Cancelled',
        ];
    }
     public function getLabel(): ?string
    {
        return match ($this) {
            self::PLANNED => 'Planned',
            self::ACTIVE => 'Active',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }
    /**
     * Colors for Filament badge columns.
     */
    public static function colors(): array
    {
        return [
            'primary' => self::PLANNED->value,
            'warning' => self::ACTIVE->value,
            'success' => self::COMPLETED->value,
            'danger'  => self::CANCELLED->value,
        ];
    }
}
