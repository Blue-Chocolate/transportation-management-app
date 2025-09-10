<?php

namespace App\Filament\Driver\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use App\Models\Trip;

class DriverTripsWidget extends BaseWidget
{
    protected function getCards(): array
    {
        return [
            Card::make('Active Trips', Trip::where('driver_id', auth()->id())->where('status', 'active')->count()),
            Card::make('Completed Trips', Trip::where('driver_id', auth()->id())->where('status', 'completed')->count()),
        ];
    }
}
