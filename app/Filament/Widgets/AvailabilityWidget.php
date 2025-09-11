<?php

namespace App\Filament\Widgets;

use App\Models\Driver;
use App\Models\Trip;

use App\Models\Vehicle;
use App\Enums\TripStatus;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AvailabilityWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $availableDrivers = Cache::remember('available_drivers_widget', now()->addMinutes(1), function () {
            $busyDrivers = Trip::query()
                ->selectRaw('COUNT(DISTINCT driver_id) as count')
                ->where('status', TripStatus::ACTIVE->value)
                ->where('start_time', '<=', now())
                ->where('end_time', '>=', now())
                ->value('count');

            return Driver::count() - $busyDrivers;
        });

        $availableVehicles = Cache::remember('available_vehicles_widget', now()->addMinutes(1), function () {
            $busyVehicles = Trip::query()
                ->selectRaw('COUNT(DISTINCT vehicle_id) as count')
                ->where('status', TripStatus::ACTIVE->value)
                ->where('start_time', '<=', now())
                ->where('end_time', '>=', now())
                ->value('count');

            return Vehicle::count() - $busyVehicles;
        });

        return [
            Stat::make('Available Drivers', $availableDrivers)
                ->description('Drivers not on active trips')
                ->icon('heroicon-o-user-group')
                ->color('success'),
            Stat::make('Available Vehicles', $availableVehicles)
                ->description('Vehicles not on active trips')
                ->icon('heroicon-o-truck')
                ->color('success'),
        ];
    }
}