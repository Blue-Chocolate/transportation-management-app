<?php

namespace App\Filament\Company\Resources;

use App\Filament\Company\Resources\VehicleResource\Pages;
use App\Models\Vehicle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

class VehicleResource extends Resource
{
    protected static ?string $model = Vehicle::class;
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationLabel = 'Vehicles';
    protected static ?string $modelLabel = 'Vehicle';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('vehicle_type')
                    ->options(['truck' => 'Truck', 'van' => 'Van', 'car' => 'Car'])
                    ->required(),
                Forms\Components\TextInput::make('registration_number')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(20),
                Forms\Components\DatePicker::make('last_maintenance')
                    ->nullable(),
                Forms\Components\Select::make('status')
                    ->options(['active' => 'Active', 'maintenance' => 'In Maintenance', 'inactive' => 'Inactive'])
                    ->required()
                    ->default('active'),
                Forms\Components\Hidden::make('company_id')
                    ->default(fn () => Auth::user()->company_id)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('vehicle_type')
                    ->sortable(),
                Tables\Columns\TextColumn::make('registration_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('last_maintenance')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'maintenance' => 'warning',
                        'inactive' => 'danger',
                    })
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('vehicle_type')
                    ->options(['truck' => 'Truck', 'van' => 'Van', 'car' => 'Car']),
                Tables\Filters\SelectFilter::make('status')
                    ->options(['active' => 'Active', 'maintenance' => 'In Maintenance', 'inactive' => 'Inactive']),
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
            'index' => Pages\ListVehicles::route('/'),
            'create' => Pages\CreateVehicle::route('/create'),
            'edit' => Pages\EditVehicle::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', Auth::user()->company_id);
    }
}