<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TripResource\Pages;
use App\Models\Trip;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Client;
use App\Enums\TripStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class TripResource extends Resource
{
    protected static ?string $model = Trip::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Operations';

    // Cache configuration
    const CACHE_TTL = 300; // 5 minutes
    const CACHE_PREFIX = 'trip_resource';

    /**
     * Get cached navigation badge (active trips)
     */
    public static function getNavigationBadge(): ?string
    {
        $userId = Auth::id();
        if (!$userId) return null;

        return Cache::store('redis')->remember(
            self::CACHE_PREFIX . "_active_badge_user_{$userId}",
            now()->addSeconds(60), // 1 minute cache for real-time badge
            function () use ($userId) {
                return Trip::query()
                    ->where('user_id', $userId)
                    ->where('start_time', '<=', now())
                    ->where('end_time', '>=', now())
                    ->where('status', TripStatus::ACTIVE->value)
                    ->count();
            }
        );
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('client_id')
                ->label('Client')
                ->options(function () {
                    return self::getCachedUserClients(Auth::id());
                })
                ->searchable()
                ->required()
                ->preload()
                ->placeholder('Select a client...')
                ->live()
                ->afterStateUpdated(function ($state) {
                    self::clearUserCaches(Auth::id());
                }),

            Select::make('driver_id')
                ->label('Driver')
                ->options(function () {
                    return self::getCachedUserDrivers(Auth::id());
                })
                ->searchable()
                ->required()
                ->preload()
                ->placeholder('Select a driver...')
                ->reactive()
                ->afterStateUpdated(function (callable $set, $state) {
                    $set('vehicle_id', null);
                    Log::info('Driver changed', ['driver_id' => $state]);
                }),

            Select::make('vehicle_id')
                ->label('Vehicle')
                ->options(function (callable $get) {
                    $driverId = $get('driver_id');
                    if (!$driverId) {
                        return [];
                    }
                    return self::getCachedDriverVehicles($driverId, Auth::id());
                })
                ->searchable()
                ->required()
                ->placeholder('Select a vehicle (choose driver first)...')
                ->live(),

            DateTimePicker::make('start_time')
                ->required()
                ->default(now()->addHour())
                ->live()
                ->afterStateUpdated(function ($state, callable $set) {
                    if ($state) {
                        $endTime = \Carbon\Carbon::parse($state)->addHour();
                        $set('end_time', $endTime->format('Y-m-d H:i:s'));
                    }
                }),

            DateTimePicker::make('end_time')
                ->required()
                ->default(now()->addHours(2))
                ->after('start_time')
                ->placeholder('End time...'),

            Select::make('status')
                ->options(TripStatus::options())
                ->default(TripStatus::PLANNED->value)
                ->required()
                ->placeholder('Select status...'),

            Forms\Components\Textarea::make('description')
                ->maxLength(500)
                ->columnSpanFull()
                ->placeholder('Optional trip description...'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('driver.name')
                    ->label('Driver')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('vehicle.name')
                    ->label('Vehicle')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('start_time')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->description(fn ($record) => $record->start_time->format('M j, g:i A')),

                TextColumn::make('end_time')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('status')
                    ->badge()
                    ->colors(TripStatus::colors())
                    ->formatStateUsing(fn ($state) => 
                        $state instanceof TripStatus 
                            ? $state->getLabel() 
                            : TripStatus::from($state)->getLabel()
                    ),

                TextColumn::make('duration')
                    ->label('Duration')
                    ->getStateUsing(function ($record) {
                        $start = \Carbon\Carbon::parse($record->start_time);
                        $end = \Carbon\Carbon::parse($record->end_time);
                        return $start->diffForHumans($end, true);
                    })
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(TripStatus::options())
                    ->default(TripStatus::PLANNED->value)
                    ->multiple(),

                Tables\Filters\SelectFilter::make('client_id')
                    ->label('Client')
                    ->options(fn() => self::getCachedUserClients(Auth::id()))
                    ->searchable(),

                Tables\Filters\SelectFilter::make('driver_id')
                    ->label('Driver')
                    ->options(fn() => self::getCachedUserDrivers(Auth::id()))
                    ->searchable(),

                Tables\Filters\Filter::make('today')
                    ->label('Today\'s Trips')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereDate('start_time', today())
                    )
                    ->toggle(),

                Tables\Filters\Filter::make('upcoming')
                    ->label('Upcoming Trips')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('start_time', '>', now())
                    )
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->after(function ($record) {
                        self::clearTripCaches($record->id);
                        self::clearUserCaches($record->user_id);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->after(function ($record) {
                        self::clearTripCaches($record->id);
                        self::clearUserCaches($record->user_id);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->after(function ($records) {
                            foreach ($records as $record) {
                                self::clearTripCaches($record->id);
                                self::clearUserCaches($record->user_id);
                            }
                        }),
                ]),
            ])
            ->defaultSort('start_time', 'desc')
            ->poll('30s'); // Auto-refresh every 30 seconds
    }

    public static function getEloquentQuery(): Builder
    {
        $userId = Auth::id();
        if (!$userId) {
            return Trip::query()->whereRaw('1 = 0');
        }

        $isCreateOperation = request()->method() === 'GET' || !request()->has('record');

        Log::info('getEloquentQuery called', [
            'is_create_operation' => $isCreateOperation,
            'user_id' => $userId,
        ]);

        if (!$isCreateOperation) {
            $tripIds = Cache::store('redis')->remember(
                self::CACHE_PREFIX . "_ids_user_{$userId}",
                now()->addSeconds(self::CACHE_TTL),
                function () use ($userId) {
                    return Trip::where('user_id', $userId)->pluck('id')->toArray();
                }
            );

            if (empty($tripIds)) {
                return Trip::query()->whereRaw('1 = 0');
            }

            return Trip::query()
                ->whereIn('id', $tripIds)
                ->with(['client', 'driver', 'vehicle'])
                ->orderBy('start_time', 'desc');
        }

        return Trip::query()
            ->where('user_id', $userId)
            ->with(['client', 'driver', 'vehicle']);
    }

    /**
     * Cache user's clients
     */
    public static function getCachedUserClients(int $userId): array
    {
        return Cache::store('redis')->remember(
            self::CACHE_PREFIX . "_user_clients_{$userId}",
            now()->addSeconds(self::CACHE_TTL),
            function () use ($userId) {
                return Client::where('user_id', $userId)
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->toArray();
            }
        );
    }

    /**
     * Cache user's drivers
     */
    public static function getCachedUserDrivers(int $userId): array
    {
        return Cache::store('redis')->remember(
            self::CACHE_PREFIX . "_user_drivers_{$userId}",
            now()->addSeconds(self::CACHE_TTL),
            function () use ($userId) {
                return Driver::where('user_id', $userId)
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->toArray();
            }
        );
    }

    /**
     * Cache vehicles for a specific driver
     */
    public static function getCachedDriverVehicles(int $driverId, int $userId): array
    {
        return Cache::store('redis')->remember(
            self::CACHE_PREFIX . "_driver_vehicles_{$driverId}_{$userId}",
            now()->addSeconds(self::CACHE_TTL),
            function () use ($driverId, $userId) {
                $driver = Driver::where('user_id', $userId)->find($driverId);
                if (!$driver) {
                    return [];
                }

                return $driver->vehicles()
                    ->where('vehicles.user_id', $userId)
                    ->orderBy('vehicles.name')
                    ->pluck('vehicles.name', 'vehicles.id')
                    ->toArray();
            }
        );
    }

    /**
     * Cache individual trip data
     */
    public static function getCachedTrip(int $tripId): ?Trip
    {
        return Cache::store('redis')->remember(
            self::CACHE_PREFIX . "_trip_{$tripId}",
            now()->addSeconds(self::CACHE_TTL),
            function () use ($tripId) {
                return Trip::with(['client', 'driver', 'vehicle', 'user'])->find($tripId);
            }
        );
    }

    /**
     * Cache trip statistics for dashboard
     */
    public static function getCachedTripStats(int $userId): array
    {
        return Cache::store('redis')->remember(
            self::CACHE_PREFIX . "_stats_user_{$userId}",
            now()->addMinutes(15), // 15 minutes cache for stats
            function () use ($userId) {
                $baseQuery = Trip::where('user_id', $userId);

                return [
                    'total' => (clone $baseQuery)->count(),
                    'today' => (clone $baseQuery)->whereDate('start_time', today())->count(),
                    'upcoming' => (clone $baseQuery)->where('start_time', '>', now())->count(),
                    'active' => (clone $baseQuery)
                        ->where('start_time', '<=', now())
                        ->where('end_time', '>=', now())
                        ->where('status', TripStatus::ACTIVE->value)
                        ->count(),
                    'completed_this_month' => (clone $baseQuery)
                        ->where('status', TripStatus::COMPLETED->value)
                        ->whereMonth('start_time', now()->month)
                        ->count(),
                ];
            }
        );
    }

    /**
     * Clear all caches related to a specific user
     */
    public static function clearUserCaches(int $userId): void
    {
        $keys = [
            self::CACHE_PREFIX . "_ids_user_{$userId}",
            self::CACHE_PREFIX . "_user_clients_{$userId}",
            self::CACHE_PREFIX . "_user_drivers_{$userId}",
            self::CACHE_PREFIX . "_active_badge_user_{$userId}",
            self::CACHE_PREFIX . "_stats_user_{$userId}",
        ];

        foreach ($keys as $key) {
            Cache::store('redis')->forget($key);
        }

        // Clear driver vehicle caches
        $driverIds = Driver::where('user_id', $userId)->pluck('id');
        foreach ($driverIds as $driverId) {
            Cache::store('redis')->forget(self::CACHE_PREFIX . "_driver_vehicles_{$driverId}_{$userId}");
        }
    }

    /**
     * Clear caches for a specific trip
     */
    public static function clearTripCaches(int $tripId): void
    {
        $keys = [
            self::CACHE_PREFIX . "_trip_{$tripId}",
        ];

        foreach ($keys as $key) {
            Cache::store('redis')->forget($key);
        }
    }

    /**
     * Warm up caches for better performance
     */
    public static function warmUpCaches(int $userId): void
    {
        self::getCachedUserClients($userId);
        self::getCachedUserDrivers($userId);
        self::getCachedTripStats($userId);
        self::getNavigationBadge();
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $userId = Auth::id();
        $data['user_id'] = $userId;
        
        // Clear caches after creation
        self::clearUserCaches($userId);
        
        return $data;
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        $userId = Auth::id();
        $data['user_id'] = $userId;
        
        // Clear caches after update
        self::clearUserCaches($userId);
        
        return $data;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrips::route('/'),
            'create' => Pages\CreateTrip::route('/create'),
            'edit' => Pages\EditTrip::route('/{record}/edit'),
        ];
    }
}