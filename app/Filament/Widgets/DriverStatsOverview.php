<?php

namespace App\Filament\Widgets;

use App\Models\Driver;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class DriverStatsOverview extends BaseWidget
{
    public ?Driver $driver = null;

    protected static bool $isLazy = false; // make it render immediately

    protected function getCards(): array
    {
        if (! $this->driver) {
            return [];
        }

        return [
            Card::make('Assigned Vehicles', $this->driver->vehicles()->count())
                ->icon('heroicon-o-truck'),

            Card::make('Completed Trips', $this->driver->trips()
                ->where('status', 'completed')
                ->count())
                ->icon('heroicon-o-check-circle')
                ->color('success'),

            Card::make('Active Trips', $this->driver->trips()
                ->where('status', 'active')
                ->count())
                ->icon('heroicon-o-clock')
                ->color('warning'),
        ];
    }
}
