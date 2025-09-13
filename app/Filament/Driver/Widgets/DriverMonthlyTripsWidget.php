<?php

namespace App\Filament\Driver\Widgets;

use App\Models\Trip;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DriverMonthlyTripsWidget extends ChartWidget
{
    protected static ?string $heading = 'Monthly Trips (This Month)';

    public function getDescription(): ?string
    {
        return 'Daily trip counts for ' . Carbon::now()->format('F Y');
    }

    protected function getData(): array
    {
        $driverId = auth('driver')->id();
        $cacheKey = 'driver_monthly_trips_chart_' . $driverId;

        $tripData = Cache::store('redis')->remember($cacheKey, now()->addMinutes(5), function () use ($driverId) {
            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth = Carbon::now()->endOfMonth();

            // Get trip counts grouped by day
            $trips = Trip::query()
                ->select(DB::raw('DATE(start_time) as trip_date'), DB::raw('COUNT(*) as trip_count'))
                ->where('driver_id', $driverId)
                ->whereBetween('start_time', [$startOfMonth, $endOfMonth])
                ->groupBy('trip_date')
                ->orderBy('trip_date')
                ->get()
                ->pluck('trip_count', 'trip_date')
                ->toArray();

            // Generate all days in the month to ensure continuous data
            $days = [];
            $currentDay = $startOfMonth->copy();
            while ($currentDay <= $endOfMonth) {
                $dateStr = $currentDay->format('Y-m-d');
                $days[$dateStr] = $trips[$dateStr] ?? 0;
                $currentDay->addDay();
            }

            return [
                'labels' => array_keys($days),
                'data' => array_values($days),
            ];
        });

        return [
            'type' => 'line',
            'data' => [
                'labels' => array_map(fn($date) => Carbon::parse($date)->format('M d'), $tripData['labels']),
                'datasets' => [
                    [
                        'label' => 'Trips',
                        'data' => $tripData['data'],
                        'borderColor' => '#3b82f6', // Vibrant blue
                        'backgroundColor' => $this->createGradient(), // Gradient fill
                        'fill' => true,
                        'tension' => 0.4, // Smooth curve
                        'pointBackgroundColor' => '#ffffff',
                        'pointBorderColor' => '#3b82f6',
                        'pointHoverBackgroundColor' => '#1e3a8a',
                        'pointHoverBorderColor' => '#ffffff',
                    ],
                ],
            ],
            'options' => [
                'plugins' => [
                    'legend' => ['display' => true],
                    'tooltip' => ['enabled' => true],
                ],
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                        'title' => ['display' => true, 'text' => 'Number of Trips'],
                    ],
                    'x' => [
                        'title' => ['display' => true, 'text' => 'Date'],
                    ],
                ],
            ],
        ];
    }

    protected function createGradient(): string
    {
        return 'linear-gradient(to top, rgba(59, 130, 246, 0.3), rgba(59, 130, 246, 0.1))';
    }

    protected function getType(): string
    {
        return 'line';
    }
}