<?php

namespace App\Filament\Widgets;

use App\Models\Trip;
use App\Enums\TripStatus;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ActiveTripsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $userId = Auth::id();
        $cacheKey = 'active_trips_widget_' . $userId;

        $counts = Cache::remember($cacheKey, now()->addMinutes(1), function () use ($userId) {
            return Trip::query()
                ->where('user_id', $userId)
                ->groupBy('status')
                ->select('status', DB::raw('count(*) as total'))
                ->pluck('total', 'status')
                ->toArray();
        });

        $colors = array_flip(TripStatus::colors());

        $stats = [];

        foreach (TripStatus::cases() as $statusEnum) {
            $status = $statusEnum->value;
            $count = $counts[$status] ?? 0;
            $label = $statusEnum->getLabel();
            $color = $colors[$status] ?? 'gray';

            $stats[] = Stat::make($label . ' Trips', $count)
                ->description('Total ' . strtolower($label) . ' trips')
                ->icon('heroicon-o-truck')
                ->color($color);
        }

        return $stats;
    }
}