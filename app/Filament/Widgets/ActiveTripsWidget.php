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
        $activeTrips = Cache::remember('active_trips', 60, function () {
            return Trip::where('start_time', '<=', now())
                ->where('end_time', '>=', now())
                ->where('status', 'active')
                ->count();
        });

        return [
            Stat::make('Active Trips', $activeTrips)
                ->description('Trips currently in progress')
                ->icon('heroicon-o-truck')
                ->color('warning'),
        ];
    }
}
