<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum EmploymentStatus: string implements HasLabel
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';

    /**
     * Get the label for Filament forms and tables.
     */
    public function getLabel(): ?string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
        };
    }
}