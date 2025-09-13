<?php

namespace App\Filament\Driver\Resources\DriverResource\RelationManagers;

use App\Models\Trip;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class TripsRelationManager extends RelationManager
{
    protected static string $relationship = 'trips';
    protected static ?string $recordTitleAttribute = 'description';

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\DateTimePicker::make('start_time')->disabled(),
                Forms\Components\DateTimePicker::make('end_time')->disabled(),
                Forms\Components\Textarea::make('description')->disabled(),
                Forms\Components\Select::make('status')
                    ->options([
                        'planned' => 'Planned',
                        'active' => 'Active',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->disabled(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $driverId = auth('driver')->id();
                $cacheKey = 'driver_trips_relation_' . $driverId;

                // Cache trip data as an array in Redis
                $tripData = Cache::store('redis')->remember($cacheKey, now()->addMinutes(5), function () use ($query) {
                    return $query
                        ->select('id', 'driver_id', 'client_id', 'vehicle_id', 'start_time', 'end_time', 'status', 'description')
                        ->with([
                            'client' => fn($q) => $q->select('id', 'name'),
                            'vehicle' => fn($q) => $q->select('id', 'name'),
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
                return $query->whereIn('id', array_column($tripData, 'id'))
                    ->with([
                        'client' => fn($q) => $q->select('id', 'name'),
                        'vehicle' => fn($q) => $q->select('id', 'name'),
                    ]);
            })
            ->columns([
                Tables\Columns\TextColumn::make('client.name')->label('Client'),
                Tables\Columns\TextColumn::make('vehicle.name')->label('Vehicle'),
                Tables\Columns\TextColumn::make('start_time')->dateTime(),
                Tables\Columns\TextColumn::make('end_time')->dateTime(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'primary' => 'planned',
                        'warning' => 'active',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                    ]),
            ])
            ->filters([])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}