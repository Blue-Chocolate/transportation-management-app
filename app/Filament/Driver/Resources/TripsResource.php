<?php

namespace App\Filament\Driver\Resources;

use App\Filament\Driver\Resources\TripsResource\Pages;
use App\Models\Trip;
use App\Enums\TripStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TripsResource extends Resource
{
    protected static ?string $model = Trip::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\DateTimePicker::make('start_time')->disabled(),
            Forms\Components\DateTimePicker::make('end_time')->disabled(),
            Forms\Components\Textarea::make('description')->disabled(),
            Forms\Components\Select::make('status')
                ->options(TripStatus::options())
                ->required()
                ->native(false),
        ]);
    }
    
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client.name')->label('Client'),
                Tables\Columns\TextColumn::make('vehicle.name')->label('Vehicle'),
                Tables\Columns\TextColumn::make('start_time')->dateTime(),
                Tables\Columns\TextColumn::make('end_time')->dateTime(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors(TripStatus::colors()),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(TripStatus::options()),
            ])
            ->actions([
                Tables\Actions\Action::make('update_status')
                    ->label('Update Status')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->options(TripStatus::options())
                            ->required()
                            ->native(false),
                    ])
                    ->action(function (Trip $record, array $data, $livewire): void {
                        Log::info('Updating trip ' . $record->id . ' to status ' . $data['status']);
                        $record->update(['status' => $data['status']]);
                        Cache::store('redis')->forget('driver_trips_' . auth('driver')->id());
                        $livewire->getTableRecords()->refresh();
                    })
                    ->icon('heroicon-o-check')
                    ->color('warning'),
            ]);
    }

    /**
     * Scope trips to the logged-in driver + eager-load relations with Redis caching
     */
    public static function getEloquentQuery(): Builder
    {
        $driverId = auth('driver')->id();
        $cacheKey = 'driver_trips_' . $driverId;

        // Cache trip data as an array in Redis
        $tripData = Cache::store('redis')->remember($cacheKey, now()->addMinutes(5), function () use ($driverId) {
            return parent::getEloquentQuery()
                ->select('trips.id', 'trips.driver_id', 'trips.client_id', 'trips.vehicle_id', 'trips.start_time', 'trips.end_time', 'trips.status', 'trips.description')
                ->where('driver_id', $driverId)
                ->with([
                    'client' => fn($query) => $query->select('id', 'name'),
                    'vehicle' => fn($query) => $query->select('id', 'name'),
                ])
                ->get()
                ->map(fn($trip) => [
                    'id' => $trip->id,
                    'driver_id' => $trip->driver_id,
                    'client_id' => $trip->client_id,
                    'vehicle_id' => $trip->vehicle_id,
                    'start_time' => $trip->start_time,
                    'end_time' => $trip->end_time,
                    'status' => $trip->status,
                    'description' => $trip->description,
                    'client_name' => $trip->client ? $trip->client->name : null,
                    'vehicle_name' => $trip->vehicle ? $trip->vehicle->name : null,
                ])
                ->toArray();
        });

        // Return a new Builder with cached trip IDs
        return parent::getEloquentQuery()
            ->whereIn('id', array_column($tripData, 'id'))
            ->with([
                'client' => fn($query) => $query->select('id', 'name'),
                'vehicle' => fn($query) => $query->select('id', 'name'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrips::route('/'),
        ];
    }
}