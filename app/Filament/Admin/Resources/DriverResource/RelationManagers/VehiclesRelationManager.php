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
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('registration_number')->label('Reg #')->searchable(),
                Tables\Columns\TextColumn::make('type')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->recordSelect(fn (Select $select) => $select->searchable()->preload())
                    ->recordSelectSearchColumns(['name', 'registration_number'])
                    ->recordSelectOptionsQuery(fn (Builder $query) =>
                        $query->where('company_id', $this->getOwnerRecord()->company_id)
                    ),
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['company_id'] = $this->getOwnerRecord()->company_id;
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DetachBulkAction::make(),
            ]);
    }
}