<?php

namespace App\Filament\Admin\Resources\DriverResource\Pages;
use  App\Filament\Admin\Resources\DriverResource;
use App\Filament\Widgets\DriverStatsOverview;
use Filament\Resources\Pages\ViewRecord;

class ViewDriver extends ViewRecord
{
    protected static string $resource = DriverResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            DriverStatsOverview::class,
        ];
    }

    protected function mutateWidgetData(array $data): array
    {
        return array_merge($data, [
            'driver' => $this->record, // inject the driver model
        ]);
    }
}
