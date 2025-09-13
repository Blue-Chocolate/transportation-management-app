<?php

namespace App\Filament\Driver\Resources;

use App\Filament\Driver\Resources\DriverResource\Pages;
use App\Filament\Driver\Resources\DriverResource\RelationManagers\TripsRelationManager;
use App\Models\Driver;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class DriverProfileResource extends Resource
{
    protected static ?string $model = Driver::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';

    /**
     * Restrict queries to the authenticated driver with Redis caching.
     */
    public static function getEloquentQuery(): Builder
{
    $driverId = auth('driver')->id();
    $cacheKey = 'driver_profile_' . $driverId;

    // Cache driver data as an array in Redis
    $driverData = Cache::store('redis')->remember($cacheKey, now()->addMinutes(5), function () use ($driverId) {
        $driver = parent::getEloquentQuery()
            ->select('id', 'name', 'email', 'phone', 'license', 'license_expiration', 'employment_status', 'medical_certified', 'performance_rating', 'created_at', 'updated_at')
            ->where('id', $driverId)
            ->with([
                'vehicles' => fn($query) => $query->select('vehicles.id', 'vehicles.name'), // Qualified columns
                'trips' => fn($query) => $query->select('id', 'driver_id', 'client_id', 'vehicle_id', 'start_time', 'end_time', 'status', 'description')
                    ->with([
                        'client' => fn($q) => $q->select('id', 'name'),
                        'vehicle' => fn($q) => $q->select('vehicles.id', 'vehicles.name'), // Qualified columns here too
                    ]),
            ])
            ->first();

        return $driver ? [
            'id' => $driver->id,
            'name' => $driver->name,
            'email' => $driver->email,
            'phone' => $driver->phone,
            'license' => $driver->license,
            'license_expiration' => $driver->license_expiration,
            'employment_status' => $driver->employment_status,
            'medical_certified' => $driver->medical_certified,
            'performance_rating' => $driver->performance_rating,
            'created_at' => $driver->created_at,
            'updated_at' => $driver->updated_at,
            'vehicles' => $driver->vehicles->map(fn($vehicle) => ['id' => $vehicle->id, 'name' => $vehicle->name])->toArray(),
            'trips' => $driver->trips->map(fn($trip) => [
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
            ])->toArray(),
        ] : [];
    });

    return parent::getEloquentQuery()
        ->where('id', $driverData['id'] ?? $driverId)
        ->with([
            'vehicles' => fn($query) => $query->select('vehicles.id', 'vehicles.name'), // Qualified columns
            'trips' => fn($query) => $query->select('id', 'driver_id', 'client_id', 'vehicle_id', 'start_time', 'end_time', 'status', 'description')
                ->with([
                    'client' => fn($q) => $q->select('id', 'name'),
                    'vehicle' => fn($q) => $q->select('vehicles.id', 'vehicles.name'), // Qualified columns
                ]),
        ]);
}

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->afterStateUpdated(function () {
                        Cache::store('redis')->forget('driver_profile_' . auth('driver')->id());
                    }),

                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->maxLength(255)
                    ->default(null)
                    ->afterStateUpdated(function () {
                        Cache::store('redis')->forget('driver_profile_' . auth('driver')->id());
                    }),

                Forms\Components\TextInput::make('email')
                    ->email()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->default(null)
                    ->afterStateUpdated(function () {
                        Cache::store('redis')->forget('driver_profile_' . auth('driver')->id());
                    }),

                Forms\Components\TextInput::make('emergency_contact')
                    ->maxLength(255)
                    ->default(null),

                Forms\Components\TextInput::make('license')
                    ->maxLength(255)
                    ->default(null),

                Forms\Components\DatePicker::make('license_expiration'),

                Forms\Components\DatePicker::make('date_of_birth'),

                Forms\Components\TextInput::make('address')
                    ->maxLength(255)
                    ->default(null),

                Forms\Components\DatePicker::make('hire_date'),

                Forms\Components\Select::make('employment_status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'suspended' => 'Suspended',
                    ])
                    ->required()
                    ->afterStateUpdated(function () {
                        Cache::store('redis')->forget('driver_profile_' . auth('driver')->id());
                    }),

                Forms\Components\Textarea::make('route_assignments')->columnSpanFull(),

                Forms\Components\TextInput::make('performance_rating')
                    ->numeric()
                    ->default(null),

                Forms\Components\Toggle::make('medical_certified')->required(),

                Forms\Components\DatePicker::make('background_check_date'),

                Forms\Components\TextInput::make('profile_photo')
                    ->maxLength(255)
                    ->default(null),

                Forms\Components\Textarea::make('notes')->columnSpanFull(),

                Forms\Components\TextInput::make('insurance_info')
                    ->maxLength(255)
                    ->default(null),

                Forms\Components\Textarea::make('training_certifications')->columnSpanFull(),

                Forms\Components\Select::make('vehicles')
                    ->label('Vehicles')
                    ->relationship('vehicles', 'name')
                    ->multiple()
                    ->preload(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('phone')->searchable(),
                Tables\Columns\TextColumn::make('license')->searchable(),
                Tables\Columns\TextColumn::make('license_expiration')->date()->sortable(),
                Tables\Columns\TextColumn::make('employment_status')->sortable(),
                Tables\Columns\IconColumn::make('medical_certified')->boolean(),
                Tables\Columns\TextColumn::make('performance_rating')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(true),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(true),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->after(function () {
                        Cache::store('redis')->forget('driver_profile_' . auth('driver')->id());
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            TripsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDrivers::route('/'),
            'edit' => Pages\EditDriver::route('/{record}/edit'),
        ];
    }
}