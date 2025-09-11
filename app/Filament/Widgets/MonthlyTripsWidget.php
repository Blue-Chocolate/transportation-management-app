<?php

namespace App\Filament\Widgets;

use App\Models\Trip;
use App\Enums\TripStatus;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class MonthlyTripsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $completedThisMonth = Cache::remember('monthly_completed_trips_widget', now()->addMinutes(5), function () {
            $startOfMonth = Carbon::now()->startOfMonth();

            return Trip::query()
                ->where('status', TripStatus::COMPLETED->value)
                ->where('end_time', '>=', $startOfMonth)
                ->where('end_time', '<=', now())
                ->count();
        });

        return [
            Stat::make('Completed Trips This Month', $completedThisMonth)
                ->description('Trips finished in the current month')
                ->icon('heroicon-o-check-circle')
                ->color('success'),
        ];
    }
}