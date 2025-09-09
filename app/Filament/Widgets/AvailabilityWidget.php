<?php

namespace App\Filament\Widgets;

use App\Models\Trip;
use App\Models\Driver;
use App\Models\Vehicle;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class AvailabilityWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $availableDrivers = Cache::remember('available_drivers', 60, function () {
            $busyDrivers = Trip::where('start_time', '<=', now())
                ->where('end_time', '>=', now())
                ->pluck('driver_id');
            return Driver::whereNotIn('id', $busyDrivers)->count();
        });

        $availableVehicles = Cache::remember('available_vehicles', 60, function () {
            $busyVehicles = Trip::where('start_time', '<=', now())
                ->where('end_time', '>=', now())
                ->pluck('vehicle_id');
            return Vehicle::whereNotIn('id', $busyVehicles)->count();
        });

        return [
            Stat::make('Available Drivers', $availableDrivers)
                ->description('Drivers not assigned right now')
                ->icon('heroicon-o-user')
                ->color('success'),

            Stat::make('Available Vehicles', $availableVehicles)
                ->description('Vehicles not in use right now')
                ->icon('heroicon-o-truck')
                ->color('success'),
        ];
    }
}