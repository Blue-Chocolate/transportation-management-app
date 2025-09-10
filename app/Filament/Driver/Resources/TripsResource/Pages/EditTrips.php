<?php

namespace App\Filament\Driver\Resources\TripsResource\Pages;

use App\Filament\Driver\Resources\TripsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTrips extends EditRecord
{
    protected static string $resource = TripsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
