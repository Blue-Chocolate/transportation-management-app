<?php

namespace App\Filament\Company\Resources;

use App\Enums\TripStatus;
use App\Filament\Company\Resources\TripResource\Pages;
use App\Models\Trip;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class TripResource extends Resource
{
    protected static ?string $model = Trip::class;
    protected static ?string $navigationIcon = 'heroicon-o-map';
    protected static ?string $navigationLabel = 'Trips';
    protected static ?string $modelLabel = 'Trip';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('client_id')
                    ->relationship('client', 'name', fn ($query) => $query->where('company_id', Auth::user()->company_id))
                    ->required(),
                Forms\Components\Select::make('driver_id')
                    ->relationship('driver', 'name', fn ($query) => $query->where('company_id', Auth::user()->company_id))
                    ->required(),
                Forms\Components\Select::make('vehicle_id')
                    ->relationship('vehicle', 'name', fn ($query) => $query->where('company_id', Auth::user()->company_id))
                    ->required(),
                Forms\Components\DateTimePicker::make('start_time')->required(),
                Forms\Components\DateTimePicker::make('end_time'),
                Forms\Components\Textarea::make('description')->maxLength(65535),
                Forms\Components\Select::make('status')
                    ->options(TripStatus::class)
                    ->required()
                    ->default(TripStatus::PLANNED),
                Forms\Components\Hidden::make('company_id')
                    ->default(fn () => Auth::user()->company_id),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client.name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('driver.name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('vehicle.name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('start_time')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('end_time')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state): string => match ($state) {
                        TripStatus::PLANNED => 'warning',
                        TripStatus::ACTIVE => 'success',
                        TripStatus::COMPLETED => 'info',
                        TripStatus::CANCELLED => 'danger',
                    })
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrips::route('/'),
            'create' => Pages\CreateTrip::route('/create'),
            'edit' => Pages\EditTrip::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', Auth::user()->company_id);
    }
}
