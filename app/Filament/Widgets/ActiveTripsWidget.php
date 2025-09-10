<?php

namespace App\Filament\Widgets;

use App\Models\Trip;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class ActiveTripsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $activeTrips = Cache::remember('active_trips_widget', 300, function () {
            return Trip::where('start_time', '<=', now())
                ->where('end_time', '>=', now())
                ->where('status', \App\Enums\TripStatus::ACTIVE->value)
                ->count();
        });

        return [
            Stat::make('Active Trips', $activeTrips)
                ->description('Trips currently in progress')
                ->descriptionIcon('heroicon-o-truck')
                ->color('warning'),
        ];
    }
}