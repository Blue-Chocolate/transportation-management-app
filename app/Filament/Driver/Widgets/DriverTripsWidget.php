<?php

namespace App\Filament\Driver\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use App\Models\Trip;

class DriverTripsWidget extends BaseWidget
{
    protected function getCards(): array
    {
        $driverId = auth('driver')->id();

        return [
            Card::make('Planned Trips', Trip::where('driver_id', $driverId)->where('status', 'planned')->count())
                ->description('Trips planned but not started yet')
                ->icon('heroicon-o-calendar')
                ->color('primary'),

            Card::make('Active Trips', Trip::where('driver_id', $driverId)->where('status', 'active')->count())
                ->description('Trips currently in progress')
                ->icon('heroicon-o-truck')
                ->color('warning'),

            Card::make('Completed Trips', Trip::where('driver_id', $driverId)->where('status', 'completed')->count())
                ->description('Trips successfully completed')
                ->icon('heroicon-o-check-circle')
                ->color('success'),

            Card::make('Cancelled Trips', Trip::where('driver_id', $driverId)->where('status', 'cancelled')->count())
                ->description('Trips that were cancelled')
                ->icon('heroicon-o-x-circle')
                ->color('danger'),
        ];
    }
}
