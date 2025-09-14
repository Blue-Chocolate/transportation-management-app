<?php

namespace App\Filament\Client\Resources;

use App\Enums\TripStatus;
use App\Filament\Client\Resources\TripsResource\Pages;
use App\Models\Driver;
use App\Models\Trip;
use App\Models\Vehicle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class TripsResource extends Resource
{
    protected static ?string $model = Trip::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Trips';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // client_id hidden (auto)
                Forms\Components\Hidden::make('client_id')
                    ->default(fn () => auth('client')->id())
                    ->required(),

                // driver select - only drivers related to the client's user_id
                Forms\Components\Select::make('driver_id')
                    ->label('Driver')
                    ->relationship(
                        'driver', 
                        'name',
                        fn (Builder $query) => $query->where('user_id', auth('client')->user()->user_id)
                    )
                    ->required()
                    ->preload()
                    ->reactive()
                    ->afterStateUpdated(fn (callable $set) => $set('vehicle_id', null)), // Reset vehicle when driver changes

                // vehicle select - only vehicles assigned to the selected driver
                Forms\Components\Select::make('vehicle_id')
                    ->label('Vehicle')
                    ->options(function (callable $get) {
                        $driverId = $get('driver_id');
                        if (!$driverId) {
                            return [];
                        }
                        
                        // Get vehicles assigned to the selected driver through pivot table
                        // Specify the table name to avoid ambiguous column reference
                        return Driver::find($driverId)
                            ->vehicles()
                            ->select('vehicles.id', 'vehicles.name')
                            ->pluck('vehicles.name', 'vehicles.id')
                            ->toArray();
                    })
                    ->required()
                    ->disabled(fn (callable $get) => !$get('driver_id'))
                    ->helperText('Please select a driver first to see available vehicles'),

                Forms\Components\DateTimePicker::make('start_time')
                    ->required()
                    ->afterOrEqual(now())
                    ->reactive()
                    ->afterStateUpdated(fn ($state, callable $set) =>
                        $set('end_time', \Carbon\Carbon::parse($state)->addHour())
                    ),

                Forms\Components\DateTimePicker::make('end_time')
                    ->required()
                    ->after('start_time'),

                Forms\Components\Textarea::make('description')
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
                Tables\Columns\TextColumn::make('driver.name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('vehicle.name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('start_time')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('end_time')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('description')->limit(50),
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
                    
                Tables\Filters\SelectFilter::make('driver_id')
                    ->label('Driver')
                    ->relationship(
                        'driver', 
                        'name',
                        fn (Builder $query) => $query->where('user_id', auth('client')->user()->user_id)
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('start_time', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        // limit trips to the logged-in client only
        // and ensure drivers belong to the client's user_id
        return parent::getEloquentQuery()
            ->where('client_id', auth('client')->id())
            ->whereHas('driver', function (Builder $query) {
                $query->where('user_id', auth('client')->user()->user_id);
            })
            ->with(['driver', 'vehicle']);
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