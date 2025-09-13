<?php

namespace App\Filament\Driver\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use App\Models\Trip;
use Livewire\Attributes\On; // For v3

class DriverTripsWidget extends BaseWidget
{
    #[On('refresh-table')] // Listen for the dispatch
    public function refresh(): void
    {
        $this->refreshAll();
    }

    protected function getCards(): array
    {
        $driverId = auth('driver')->id();

        return [
            Card::make('Planned Trips', Trip::where('driver_id', $driverId)->where('status', 'planned')->count())
                ->description('Trips planned but not started yet')
                ->icon('heroicon-o-calendar')
                ->color('primary'),
            // ... other cards unchanged
        ];
    }
}