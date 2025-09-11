<?php

namespace App\Filament\Driver\Resources\DriverResource\Pages;

use App\Filament\Driver\Resources\DriverProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDriver extends EditRecord
{
    protected static string $resource = DriverProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}