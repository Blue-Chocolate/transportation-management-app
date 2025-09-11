<?php

namespace App\Filament\Admin\Resources\DriverResource\RelationManagers;

use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VehiclesRelationManager extends RelationManager
{
    protected static string $relationship = 'vehicles';
    protected static ?string $recordTitleAttribute = 'name';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('registration_number')
                    ->label('Reg #')
                    ->searchable(),
                Tables\Columns\TextColumn::make('vehicle_type') // غيّرت من type → vehicle_type
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->recordSelect(fn (Select $select) => $select->searchable()->preload())
                    ->recordSelectSearchColumns(['name', 'registration_number']),
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DetachBulkAction::make(),
            ]);
    }
}
