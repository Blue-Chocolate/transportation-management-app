<?php

namespace App\Filament\Driver\Resources\TripsResource\Pages;

use App\Filament\Driver\Resources\TripsResource;
use Filament\Resources\Pages\ListRecords;

class ListTrips extends ListRecords
{
    protected static string $resource = TripsResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}