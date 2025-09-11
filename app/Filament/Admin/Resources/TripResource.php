<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TripResource\Pages;
use App\Filament\Resources\TripResource\RelationManagers;
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

class TripResource extends Resource
{
    protected static ?string $model = Trip::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationBadge(): ?string
    {
        return Trip::query()
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
                ->relationship('client', 'name')
                ->searchable()
                ->preload()
                ->required(),

            Select::make('driver_id')
                ->label('Driver')
                ->relationship('driver', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->reactive()
                ->afterStateUpdated(function (callable $set) {
                    $set('vehicle_id', null); // Reset vehicle when driver changes
                }),

            Select::make('vehicle_id')
                ->label('Vehicle')
                ->options(function (callable $get) {
                    $driverId = $get('driver_id');
                    if (!$driverId) {
                        return [];
                    }
                    $driver = Driver::find($driverId);
                    return $driver ? $driver->vehicles->pluck('name', 'id') : [];
                })
                ->searchable()
                ->preload()
                ->required()
                ->reactive(),

            DateTimePicker::make('start_time')
                ->required()
                ->default(now())
                ->reactive(),

            DateTimePicker::make('end_time')
                ->required()
                ->default(now()->addHours(1))
                ->after('start_time')
                ->reactive(),

            Select::make('status')
                ->options(TripStatus::options())
                ->default(TripStatus::PLANNED->value)
                ->required(),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrips::route('/'),
            'create' => Pages\CreateTrip::route('/create'),
            'edit' => Pages\EditTrip::route('/{record}/edit'),
        ];
    }
}
