<?php

namespace App\Filament\Client\Resources;

use App\Enums\TripStatus;
use App\Filament\Client\Resources\TripsResource\Pages;
use App\Models\Trip;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class TripsResource extends Resource
{
    protected static ?string $model = Trip::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Trips';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Hidden::make('client_id')
                ->default(fn () => auth('client')->id())
                ->required(),

            Forms\Components\Select::make('driver_id')
                ->label('Driver')
                ->relationship('driver', 'name', fn ($query) => $query->where('company_id', auth('client')->user()->company_id))
                ->required()
                ->searchable()
                ->preload()
                ->reactive()
                ->afterStateUpdated(fn (callable $set) => $set('vehicle_id', null)),

            Forms\Components\Select::make('vehicle_id')
                ->label('Vehicle')
                ->options(function (callable $get) {
                    $driverId = $get('driver_id');
                    if (!$driverId) {
                        return [];
                    }
                    return \App\Models\Driver::find($driverId)
                        ->vehicles()
                        ->where('vehicles.company_id', auth('client')->user()->company_id)
                        ->pluck('vehicles.name', 'vehicles.id');
                })
                ->required()
                ->searchable()
                ->preload()
                ->reactive(),

            Forms\Components\DateTimePicker::make('start_time')
                ->label('Start Time')
                ->seconds(false)
                ->native(false)
                ->default(fn () => now()->addMinutes(5)->format('Y-m-d\TH:i'))
                ->required()
                ->afterOrEqual(now())
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set) {
                    if ($state) {
                        $set('end_time', Carbon::parse($state)->addHour()->format('Y-m-d\TH:i'));
                    }
                }),

            Forms\Components\DateTimePicker::make('end_time')
                ->label('End Time')
                ->seconds(false)
                ->native(false)
                ->default(fn () => now()->addHour()->addMinutes(5)->format('Y-m-d\TH:i'))
                ->required()
                ->after('start_time'),

            Forms\Components\Textarea::make('description')
                ->label('Description')
                ->placeholder('Optional notes about this trip...')
                ->columnSpanFull(),

            Forms\Components\Select::make('status')
                ->options(TripStatus::options())
                ->default(TripStatus::PLANNED->value)
                ->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('driver.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('vehicle.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_time')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_time')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors(TripStatus::colors())
                    ->formatStateUsing(fn ($state) =>
                        $state instanceof TripStatus
                            ? $state->getLabel()
                            : TripStatus::from($state)->getLabel()
                    ),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(TripStatus::options())
                    ->default(TripStatus::PLANNED->value),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->after(function () {
                        Cache::store('redis')->forget('client_trips_' . auth('client')->id());
                    }),
                Tables\Actions\DeleteAction::make()
                    ->after(function () {
                        Cache::store('redis')->forget('client_trips_' . auth('client')->id());
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->after(function () {
                        Cache::store('redis')->forget('client_trips_' . auth('client')->id());
                    }),
            ])
            ->defaultSort('start_time', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        $clientId = auth('client')->id();
        $cacheKey = 'client_trips_' . $clientId;

        $tripData = Cache::store('redis')->remember($cacheKey, now()->addMinutes(5), function () use ($clientId) {
            return parent::getEloquentQuery()
                ->select('trips.id', 'trips.client_id', 'trips.driver_id', 'trips.vehicle_id', 'trips.start_time', 'trips.end_time', 'trips.status', 'trips.description')
                ->where('client_id', $clientId)
                ->with([
                    'driver' => fn($query) => $query->select('id', 'name'),
                    'vehicle' => fn($query) => $query->select('id', 'name'),
                ])
                ->get()
                ->map(fn($trip) => [
                    'id' => $trip->id,
                    'client_id' => $trip->client_id,
                    'driver_id' => $trip->driver_id,
                    'vehicle_id' => $trip->vehicle_id,
                    'start_time' => $trip->start_time,
                    'end_time' => $trip->end_time,
                    'status' => $trip->status,
                    'description' => $trip->description,
                    'driver_name' => $trip->driver ? $trip->driver->name : null,
                    'vehicle_name' => $trip->vehicle ? $trip->vehicle->name : null,
                ])
                ->toArray();
        });

        return parent::getEloquentQuery()
            ->whereIn('id', array_column($tripData, 'id'))
            ->with([
                'driver' => fn($query) => $query->select('id', 'name'),
                'vehicle' => fn($query) => $query->select('id', 'name'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTrips::route('/'),
            'create' => Pages\CreateTrips::route('/create'),
            'view'   => Pages\ViewTrip::route('/{record}'),
            'edit'   => Pages\EditTrip::route('/{record}/edit'),
        ];
    }
}