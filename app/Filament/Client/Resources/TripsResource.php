<?php

namespace App\Filament\Client\Resources;

use App\Filament\Client\Resources\TripsResource\Pages;
use App\Models\Trip;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;
use APP\Models\User;

class TripsResource extends Resource
{
    protected static ?string $model = Trip::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    // Handle driver and vehicle assignment before creating a trip
    protected function mutateFormDataBeforeCreate(array $data): array
    {
      $driver = \App\Services\DriverAssignmentService::assignDriver(
    $data['vehicle_type'],
    $data['start_time'],
    $data['end_time']
);

if (!$driver) {
    throw ValidationException::withMessages([
        'vehicle_type' => 'No available drivers for this vehicle type at the selected time.',
    ]);
}

        // Pick first vehicle of the selected type assigned to the driver
        $vehicle = $driver->vehicles()->where('type', $data['vehicle_type'])->first();

        if (!$vehicle) {
            throw ValidationException::withMessages([
                'vehicle_type' => 'Assigned driver does not have a vehicle of the selected type.',
            ]);
        }

       $data['driver_id'] = $driver->id;
       $data['vehicle_id'] = $driver->assignedVehicle->id; // adjust if necessary


        return $data;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
            Forms\Components\Select::make('company_id')
                ->label('Company')
                ->relationship('company', 'name') // assumes Trip model has a `company()` relation
                ->required(),

            Forms\Components\Select::make('vehicle_type')
                ->label('Vehicle Type')
                ->options([
                    'car' => 'Car',
                    'van' => 'Van',
                    'truck' => 'Truck',
                ])
                ->required(),

            Forms\Components\DateTimePicker::make('start_time')
                ->label('Start Time')
                ->required(),

            Forms\Components\DateTimePicker::make('end_time')
                ->label('End Time')
                ->required(),

            Forms\Components\Hidden::make('driver_id'),
            Forms\Components\Hidden::make('vehicle_id'),
        ]);
}

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('driver.name')->label('Driver'),
                Tables\Columns\TextColumn::make('vehicle.name')->label('Vehicle'),
                Tables\Columns\TextColumn::make('vehicle.type')->label('Vehicle Type'),
                Tables\Columns\TextColumn::make('start_time')->label('Start Time')->dateTime(),
                Tables\Columns\TextColumn::make('end_time')->label('End Time')->dateTime(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrips::route('/'),
            'create' => Pages\CreateTrips::route('/create'),
            'edit' => Pages\EditTrips::route('/{record}/edit'),
        ];
    }
}
