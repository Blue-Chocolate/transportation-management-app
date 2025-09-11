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
                    ->options(TripStatus::class),
            ])
            ->actions([
                Tables\Actions\Action::make('update_status')
                    ->label('Update Status')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->options(TripStatus::class)
                            ->required(),
                    ])
                    ->action(function (Trip $record, array $data): void {
                        $record->update(['status' => $data['status']]);
                    })
                    ->icon('heroicon-o-check')
                    ->color('warning'),
            ]);
    }

    /**
     * Scope trips to the logged-in driver + eager-load relations
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('driver_id', auth('driver')->id())
            ->with(['client', 'vehicle']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrips::route('/'),
            // 🚫 removed Edit page for drivers
        ];
    }
}