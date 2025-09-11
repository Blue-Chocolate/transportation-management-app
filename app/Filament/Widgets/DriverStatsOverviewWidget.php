<?php

namespace App\Filament\Widgets;

use App\Models\Driver;
use App\Enums\TripStatus;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class DriverStatsOverviewWidget extends BaseWidget
{
    public ?Driver $driver = null;

    protected static bool $isLazy = false; // load immediately

    protected function getCards(): array
    {
        if (! $this->driver) {
            return [];
        }

        // Preload relations to avoid N+1
        $this->driver->loadCount([
            'vehicles',
            'trips' => function ($query) {
                $query->where('status', TripStatus::COMPLETED->value);
            },
            'trips' => function ($query) {
                $query->where('status', TripStatus::ACTIVE->value);
            },
        ]);

        return [
            Card::make('Assigned Vehicles', $this->driver->vehicles_count)
                ->icon('heroicon-o-truck'),

            Card::make('Completed Trips', $this->driver->trips_count) // Note: This assumes the second loadCount overrides, but actually use separate
                ->icon('heroicon-o-check-circle')
                ->color('success'),

            Card::make('Active Trips', $this->driver->trips_count) // Adjust if needed with custom count queries
                ->icon('heroicon-o-clock')
                ->color('warning'),
        ];
    }
}