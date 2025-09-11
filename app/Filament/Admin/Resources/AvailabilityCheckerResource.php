<?php

namespace App\Filament\Admin\Resources;

use App\Enums\TripStatus;
use App\Filament\Admin\Resources\AvailabilityCheckerResource\Pages;
use App\Models\Driver;
use App\Models\Vehicle;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Filters\Filter;

class AvailabilityCheckerResource extends Resource
{
    protected static ?string $model = Driver::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Availability Checker';
    protected static ?string $navigationGroup = 'Operations';
    protected static ?int $navigationSort = 3;

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            DateTimePicker::make('start_time')
                ->required()
                ->default(now())
                ->reactive(),
            DateTimePicker::make('end_time')
                ->required()
                ->default(now()->addHours(1))
                ->after('start_time')
                ->reactive(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(function () {
                // Get time range from filters or request, default to now and now + 1 hour
                $start = request()->input('start_time') ? Carbon::parse(request()->input('start_time')) : now();
                $end = request()->input('end_time') ? Carbon::parse(request()->input('end_time')) : now()->addHours(1);

                // Subquery for conflicting driver trips
                $conflictingDriverTrips = DB::table('trips')
                    ->select('driver_id')
                    ->where('start_time', '<', $end)
                    ->where('end_time', '>', $start)
                    ->whereIn('status', [TripStatus::PLANNED->value, TripStatus::ACTIVE->value])
                    ->groupBy('driver_id');

                // Subquery for conflicting vehicle trips
                $conflictingVehicleTrips = DB::table('trips')
                    ->select('vehicle_id')
                    ->where('start_time', '<', $end)
                    ->where('end_time', '>', $start)
                    ->whereIn('status', [TripStatus::PLANNED->value, TripStatus::ACTIVE->value])
                    ->groupBy('vehicle_id');

                // Query drivers who are not in conflicting trips
                $query = Driver::query()
                    ->whereNotExists(function ($query) use ($conflictingDriverTrips) {
                        $query->select(DB::raw(1))
                            ->fromSub($conflictingDriverTrips, 'conflicts')
                            ->whereColumn('conflicts.driver_id', 'drivers.id');
                    });

                // Ensure at least one vehicle is available
                $availableVehicles = Vehicle::query()
                    ->whereNotExists(function ($query) use ($conflictingVehicleTrips) {
                        $query->select(DB::raw(1))
                            ->fromSub($conflictingVehicleTrips, 'conflicts')
                            ->whereColumn('conflicts.vehicle_id', 'vehicles.id');
                    })
                    ->exists();

                // If no vehicles are available, return an empty query
                if (!$availableVehicles) {
                    return $query->whereRaw('1 = 0'); // No drivers can be listed if no vehicles are available
                }

                return $query
                    ->select([
                        'drivers.id',
                        'drivers.name as driver_name',
                        'drivers.phone',
                        'drivers.email',
                    ])
                    ->orderBy('drivers.name');
            })
            ->columns([
                TextColumn::make('driver_name')
                    ->label('Driver Name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('status')
                    ->getStateUsing(fn () => 'Available')
                    ->badge()
                    ->color('success'),
            ])
            ->filters([
                Filter::make('time_range')
                    ->form([
                        DateTimePicker::make('start_time')
                            ->label('Start Time')
                            ->default(Carbon::parse('2025-09-15 18:00:00')) // Default to 6 PM, Sept 15, 2025
                            ->required(),
                        DateTimePicker::make('end_time')
                            ->label('End Time')
                            ->default(Carbon::parse('2025-09-15 19:00:00')) // Default to 7 PM, Sept 15, 2025
                            ->after('start_time')
                            ->required(),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['start_time']) && !empty($data['end_time'])) {
                            $start = Carbon::parse($data['start_time']);
                            $end = Carbon::parse($data['end_time']);

                            // Subquery for conflicting driver trips
                            $conflictingDriverTrips = DB::table('trips')
                                ->select('driver_id')
                                ->where('start_time', '<', $end)
                                ->where('end_time', '>', $start)
                                ->whereIn('status', [TripStatus::PLANNED->value, TripStatus::ACTIVE->value])
                                ->groupBy('driver_id');

                            // Apply driver availability filter
                            $query->whereNotExists(function ($query) use ($conflictingDriverTrips) {
                                $query->select(DB::raw(1))
                                    ->fromSub($conflictingDriverTrips, 'conflicts')
                                    ->whereColumn('conflicts.driver_id', 'drivers.id');
                            });
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View Profile')
                    ->url(fn ($record) => route('filament.admin.resources.drivers.edit', $record->id)) // Ensure this route exists
                    ->icon('heroicon-o-eye'),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAvailability::route('/'),
        ];
    }

    // Helper method to get available vehicles
    public static function getAvailableVehicles($start_time, $end_time)
    {
        $start = Carbon::parse($start_time);
        $end = Carbon::parse($end_time);

        $conflictingTrips = DB::table('trips')
            ->select('vehicle_id')
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->whereIn('status', [TripStatus::PLANNED->value, TripStatus::ACTIVE->value])
            ->groupBy('vehicle_id');

        return Vehicle::query()
            ->whereNotExists(function ($query) use ($conflictingTrips) {
                $query->select(DB::raw(1))
                    ->fromSub($conflictingTrips, 'conflicts')
                    ->whereColumn('conflicts.vehicle_id', 'vehicles.id');
            })
            ->orderBy('name')
            ->get();
    }
}