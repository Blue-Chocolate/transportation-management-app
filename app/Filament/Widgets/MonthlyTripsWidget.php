<?php

namespace App\Filament\Widgets;

use App\Models\Trip;
use App\Enums\TripStatus;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class MonthlyTripsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $userId = Auth::id();
        $cacheKey = 'monthly_completed_trips_widget_' . $userId;

        $completedThisMonth = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($userId) {
            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth = Carbon::now()->endOfMonth();

            return Trip::query()
                ->where('user_id', $userId)
                ->where('status', TripStatus::COMPLETED->value)
                ->where('end_time', '>=', $startOfMonth)
                ->where('end_time', '<=', $endOfMonth)
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