<?php

namespace App\Filament\Widgets;

use App\Models\Trip;
use App\Enums\TripStatus;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ActiveTripsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $activeTrips = Cache::remember('active_trips_widget', now()->addMinutes(1), function () {
            return Trip::query()
                ->where('status', TripStatus::ACTIVE->value)
                ->where('start_time', '<=', now())
                ->where('end_time', '>=', now())
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