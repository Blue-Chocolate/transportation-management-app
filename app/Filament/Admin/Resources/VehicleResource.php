<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\VehicleResource\Pages;
use App\Models\Vehicle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class VehicleResource extends Resource
{
    protected static ?string $model = Vehicle::class;
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'Operations';

    // Cache configuration
    const CACHE_TTL = 300; // 5 minutes
    const CACHE_PREFIX = 'vehicle_resource';

    /**
     * Get cached navigation badge count
     */
    public static function getNavigationBadge(): ?string
    {
        $userId = Auth::id();
        if (!$userId) return null;

        $count = Cache::store('redis')->remember(
            self::CACHE_PREFIX . "_badge_count_user_{$userId}",
            now()->addSeconds(60), // 1 minute cache for badge
            function () use ($userId) {
                return Vehicle::where('user_id', $userId)->count();
            }
        );

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, $component) {
                    // Clear related caches when form data changes
                    self::clearUserCaches(Auth::id());
                }),

            Forms\Components\TextInput::make('registration_number')
                ->label('Registration Number')
                ->maxLength(255)
                ->nullable()
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, $component) {
                    self::clearUserCaches(Auth::id());
                }),

            Forms\Components\Select::make('vehicle_type')
                ->label('Vehicle Type')
                ->options(self::getCachedVehicleTypes())
                ->required()
                ->searchable()
                ->preload(),

            Forms\Components\Toggle::make('is_active')
                ->label('Active')
                ->default(true),

            Forms\Components\Textarea::make('description')
                ->label('Description')
                ->maxLength(500)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('registration_number')
                    ->label('Reg. Number')
                    ->searchable()
                    ->copyable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('vehicle_type')
                    ->label('Type')
                    ->badge()
                    ->color('info'),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active')
                    ->afterStateUpdated(function ($record, $state) {
                        self::clearUserCaches($record->user_id);
                        self::clearVehicleCaches($record->id);
                    }),

                Tables\Columns\TextColumn::make('trips_count')
                    ->label('Total Trips')
                    ->counts('trips')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('vehicle_type')
                    ->label('Vehicle Type')
                    ->options(self::getCachedVehicleTypes())
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->after(function ($record) {
                        self::clearVehicleCaches($record->id);
                        self::clearUserCaches($record->user_id);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->after(function ($record) {
                        self::clearVehicleCaches($record->id);
                        self::clearUserCaches($record->user_id);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->after(function ($records) {
                        foreach ($records as $record) {
                            self::clearVehicleCaches($record->id);
                            self::clearUserCaches($record->user_id);
                        }
                    }),
            ])
            ->defaultSort('name', 'asc')
            ->poll('30s'); // Auto-refresh every 30 seconds
    }

    public static function getEloquentQuery(): Builder
    {
        $userId = Auth::id();
        if (!$userId) {
            return Vehicle::query()->whereRaw('1 = 0');
        }

        // Cache vehicle IDs for this user
        $vehicleIds = Cache::store('redis')->remember(
            self::CACHE_PREFIX . "_ids_user_{$userId}",
            now()->addSeconds(self::CACHE_TTL),
            function () use ($userId) {
                return Vehicle::where('user_id', $userId)
                    ->pluck('id')
                    ->toArray();
            }
        );

        if (empty($vehicleIds)) {
            return Vehicle::query()->whereRaw('1 = 0');
        }

        return Vehicle::query()
            ->whereIn('id', $vehicleIds)
            ->with(['trips' => function ($query) {
                $query->select('id', 'vehicle_id'); // Minimal data for counts
            }])
            ->orderBy('name', 'asc');
    }

    /**
     * Get cached vehicle types for dropdown
     */
    protected static function getCachedVehicleTypes(): array
    {
        return Cache::store('redis')->remember(
            self::CACHE_PREFIX . '_vehicle_types',
            now()->addHours(24), // Cache for 24 hours
            function () {
                return [
                    'sedan' => 'Sedan',
                    'suv' => 'SUV',
                    'truck' => 'Truck',
                    'van' => 'Van',
                    'bus' => 'Bus',
                    'motorcycle' => 'Motorcycle',
                    'other' => 'Other',
                ];
            }
        );
    }

    /**
     * Cache user's vehicles for quick access
     */
    public static function getCachedUserVehicles(int $userId): array
    {
        return Cache::store('redis')->remember(
            self::CACHE_PREFIX . "_user_vehicles_{$userId}",
            now()->addSeconds(self::CACHE_TTL),
            function () use ($userId) {
                return Vehicle::where('user_id', $userId)
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->toArray();
            }
        );
    }

    /**
     * Cache individual vehicle data
     */
    public static function getCachedVehicle(int $vehicleId): ?Vehicle
    {
        return Cache::store('redis')->remember(
            self::CACHE_PREFIX . "_vehicle_{$vehicleId}",
            now()->addSeconds(self::CACHE_TTL),
            function () use ($vehicleId) {
                return Vehicle::with(['user', 'trips'])->find($vehicleId);
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
            self::CACHE_PREFIX . "_user_vehicles_{$userId}",
            self::CACHE_PREFIX . "_badge_count_user_{$userId}",
            "trip_resource_ids_user_{$userId}", // Clear related trip caches
            "driver_resource_ids_user_{$userId}", // Clear related driver caches
        ];

        foreach ($keys as $key) {
            Cache::store('redis')->forget($key);
        }
    }

    /**
     * Clear caches for a specific vehicle
     */
    public static function clearVehicleCaches(int $vehicleId): void
    {
        $keys = [
            self::CACHE_PREFIX . "_vehicle_{$vehicleId}",
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
        // Pre-load frequently accessed data
        self::getCachedUserVehicles($userId);
        self::getNavigationBadge();
        
        // Pre-load vehicle IDs
        Cache::store('redis')->remember(
            self::CACHE_PREFIX . "_ids_user_{$userId}",
            now()->addSeconds(self::CACHE_TTL),
            function () use ($userId) {
                return Vehicle::where('user_id', $userId)->pluck('id')->toArray();
            }
        );
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
        return [
            // Add relations if needed
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVehicles::route('/'),
            'create' => Pages\CreateVehicle::route('/create'),
            'edit' => Pages\EditVehicle::route('/{record}/edit'),
        ];
    }
}