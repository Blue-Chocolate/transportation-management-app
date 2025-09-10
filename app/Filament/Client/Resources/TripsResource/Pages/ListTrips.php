<?php

namespace App\Filament\Client\Resources\TripsResource\Pages;

use App\Filament\Client\Resources\TripsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTrips extends ListRecords
{
    protected static string $resource = TripsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
