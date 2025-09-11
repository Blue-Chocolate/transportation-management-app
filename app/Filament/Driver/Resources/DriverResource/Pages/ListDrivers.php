<?php

namespace App\Filament\Driver\Resources\DriverResource\Pages;

use App\Filament\Driver\Resources\DriverProfileResource;
use Filament\Resources\Pages\ListRecords;

class ListDrivers extends ListRecords
{
    protected static string $resource = DriverProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}