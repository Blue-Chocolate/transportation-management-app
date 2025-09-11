<?php

namespace App\Filament\Admin\Resources\AvailabilityCheckerResource\Pages;

use App\Filament\Admin\Resources\AvailabilityCheckerResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
use Filament\Forms\Components\DateTimePicker;

class ListAvailability extends ListRecords
{
    protected static string $resource = AvailabilityCheckerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('check_availability')
                ->form([
                    DateTimePicker::make('start_time')
                        ->required()
                        ->default(now()),
                    DateTimePicker::make('end_time')
                        ->required()
                        ->default(now()->addHours(1))
                        ->after('start_time'),
                ])
                ->action(function (array $data) {
                    $this->redirect(route('filament.admin.resources.availability-checkers.index', [
                        'start_time' => $data['start_time'],
                        'end_time' => $data['end_time'],
                    ]));
                }),
        ];
    }
}