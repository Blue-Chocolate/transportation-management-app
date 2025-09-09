<?php

namespace App\Filament\Widgets;

use App\Models\Trip;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class MonthlyTripsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $completedTrips = Cache::remember('completed_trips_month', 60, function () {
            return Trip::where('status', 'completed')
                ->whereBetween('end_time', [now()->startOfMonth(), now()->endOfMonth()])
                ->count();
        });

        return [
            Stat::make('Trips Completed This Month', $completedTrips)
                ->description('Successful trips this month')
                ->icon('heroicon-o-check-circle')
                ->color('primary'),
        ];
    }
}