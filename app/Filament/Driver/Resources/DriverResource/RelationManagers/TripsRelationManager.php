<?php

namespace App\Filament\Driver\Resources\DriverResource\RelationManagers;

use App\Models\Trip;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class TripsRelationManager extends RelationManager
{
    protected static string $relationship = 'trips';
    protected static ?string $recordTitleAttribute = 'description';

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\DateTimePicker::make('start_time')->disabled(),
                Forms\Components\DateTimePicker::make('end_time')->disabled(),
                Forms\Components\Textarea::make('description')->disabled(),
                Forms\Components\Select::make('status')
                    ->options([
                        'planned'   => 'Planned',
                        'active'    => 'Active',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->disabled(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client.name')->label('Client'),
                Tables\Columns\TextColumn::make('vehicle.name')->label('Vehicle'),
                Tables\Columns\TextColumn::make('start_time')->dateTime(),
                Tables\Columns\TextColumn::make('end_time')->dateTime(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'primary' => 'planned',
                        'warning' => 'active',
                        'success' => 'completed',
                        'danger'  => 'cancelled',
                    ]),
            ])
            ->filters([])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
