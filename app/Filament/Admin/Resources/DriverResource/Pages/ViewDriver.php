<?php

namespace App\Filament\Admin\Resources\DriverResource\Pages;

use App\Filament\Admin\Resources\DriverResource;
use Filament\Resources\Pages\ViewRecord;

class ViewDriver extends ViewRecord
{
    protected static string $resource = DriverResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Preload relations to avoid N+1 in infolist
        $this->record->load('vehicles');

        return $data;
    }
}