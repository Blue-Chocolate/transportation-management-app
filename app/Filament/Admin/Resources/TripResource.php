<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TripResource\Pages;
use App\Models\Trip;
use App\Models\Driver;
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

class TripResource extends Resource
{
    protected static ?string $model = Trip::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationBadge(): ?string
    {
        $userId = \Illuminate\Support\Facades\Auth::id();

        return Trip::query()
            ->where('user_id', $userId)
            ->where('start_time', '<=', now())
            ->where('end_time', '>=', now())
            ->where('status', TripStatus::ACTIVE->value)
            ->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning'; // yellow badge
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('client_id')
                ->label('Client')
                ->relationship(name: 'client', titleAttribute: 'name', modifyQueryUsing: fn (Builder $query) => $query->where('user_id', \Illuminate\Support\Facades\Auth::id()))
                ->searchable()
                ->required()
                ->placeholder('Select a client...'),

            Select::make('driver_id')
                ->label('Driver')
                ->relationship(name: 'driver', titleAttribute: 'name', modifyQueryUsing: fn (Builder $query) => $query->where('user_id', \Illuminate\Support\Facades\Auth::id()))
                ->searchable()
                ->required()
                ->placeholder('Select a driver...')
                ->reactive() // Only reactive field to populate vehicle_id
                ->afterStateUpdated(function (callable $set, $state) {
                    $set('vehicle_id', null);
                    Log::info('Driver changed', ['driver_id' => $state]);
                }),

            Select::make('vehicle_id')
                ->label('Vehicle')
                ->options(function (callable $get) {
                    $driverId = $get('driver_id');
                    if (! $driverId) {
                        Log::info('Vehicle options: No driver selected');
                        return [];
                    }

                    $driver = Driver::where('user_id', \Illuminate\Support\Facades\Auth::id())->find($driverId);
                    if (! $driver) {
                        Log::info('Vehicle options: Invalid driver', ['driver_id' => $driverId]);
                        return [];
                    }

                    $options = $driver->vehicles()
                        ->where('vehicles.user_id', \Illuminate\Support\Facades\Auth::id())
                        ->select('vehicles.id', 'vehicles.name')
                        ->get()
                        ->pluck('name', 'id')
                        ->toArray();

                    Log::info('Vehicle options loaded', ['driver_id' => $driverId, 'count' => count($options)]);
                    return $options;
                })
                ->searchable()
                ->required()
                ->placeholder('Select a vehicle (choose driver first)...'),

            DateTimePicker::make('start_time')
                ->required()
                ->default(now()->addHour()),

            DateTimePicker::make('end_time')
                ->required()
                ->default(now()->addHours(2))
                ->placeholder('End time...'),

            Select::make('status')
                ->options(TripStatus::options())
                ->default(TripStatus::PLANNED->value)
                ->required()
                ->placeholder('Select status...'),
        ])->extraAttributes([
            'wire:submit' => 'console.log("Form submitted", $wire.getFormData())', // Client-side debug
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('client.name')->label('Client'),
                TextColumn::make('driver.name')->label('Driver'),
                TextColumn::make('vehicle.name')->label('Vehicle'),
                TextColumn::make('start_time')->dateTime(),
                TextColumn::make('end_time')->dateTime(),
                TextColumn::make('status')->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(TripStatus::options())
                    ->default(TripStatus::PLANNED->value)
                    ->query(fn (Builder $query, array $data): Builder =>
                        $query->when($data['value'] ?? null, fn ($q) => $q->where('status', $data['value']))
                    ),
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

    public static function getEloquentQuery(): Builder
    {
        $userId = \Illuminate\Support\Facades\Auth::id();
        $isCreateOperation = request()->method() === 'GET' || !request()->has('record');

        Log::info('getEloquentQuery called', [
            'is_create_operation' => $isCreateOperation,
            'method' => request()->method(),
            'has_record' => request()->has('record'),
            'user_id' => $userId,
        ]);

        $query = Trip::query()
            ->where('user_id', $userId)
            ->with(['client', 'driver', 'vehicle']);

        if (! $isCreateOperation) {
            $tripIds = Cache::store('redis')->remember("trip_ids_user_{$userId}", now()->addSeconds(30), function () use ($userId) {
                return Trip::where('user_id', $userId)->pluck('id')->toArray();
            });

            if (empty($tripIds)) {
                Log::info('getEloquentQuery: No trips found for user', ['user_id' => $userId]);
                return Trip::query()->whereRaw('1 = 0');
            }

            $query->whereIn('id', $tripIds);
            $query->orderBy('start_time', 'asc');
            Log::info('getEloquentQuery: List/Edit mode', ['user_id' => $userId, 'trip_ids_count' => count($tripIds)]);
        } else {
            Log::info('getEloquentQuery: Create mode', ['user_id' => $userId]);
        }

        return $query;
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = \Illuminate\Support\Facades\Auth::id();
        Log::info('Resource mutateFormDataBeforeCreate', ['data' => $data]);
        return $data;
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        $data['user_id'] = \Illuminate\Support\Facades\Auth::id();
        return $data;
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