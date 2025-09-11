<?php

namespace App\Filament\Driver\Resources;

use App\Filament\Driver\Resources\DriverResource\Pages;
use App\Filament\Driver\Resources\DriverResource\RelationManagers\TripsRelationManager;
use App\Models\Driver;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DriverProfileResource extends Resource
{
    protected static ?string $model = Driver::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';

    /**
     * Restrict queries to the authenticated driver.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('id', auth('driver')->id())
            ->with('vehicles');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->maxLength(255)
                    ->default(null),

                Forms\Components\TextInput::make('email')
                    ->email()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->default(null),

                Forms\Components\TextInput::make('emergency_contact')
                    ->maxLength(255)
                    ->default(null),

                Forms\Components\TextInput::make('license')
                    ->maxLength(255)
                    ->default(null),

                Forms\Components\DatePicker::make('license_expiration'),

                Forms\Components\DatePicker::make('date_of_birth'),

                Forms\Components\TextInput::make('address')
                    ->maxLength(255)
                    ->default(null),

                Forms\Components\DatePicker::make('hire_date'),

                Forms\Components\Select::make('employment_status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'suspended' => 'Suspended',
                    ])
                    ->required(),

                Forms\Components\Textarea::make('route_assignments')->columnSpanFull(),

                Forms\Components\TextInput::make('performance_rating')
                    ->numeric()
                    ->default(null),

                Forms\Components\Toggle::make('medical_certified')->required(),

                Forms\Components\DatePicker::make('background_check_date'),

                Forms\Components\TextInput::make('profile_photo')
                    ->maxLength(255)
                    ->default(null),

                Forms\Components\Textarea::make('notes')->columnSpanFull(),

                Forms\Components\TextInput::make('insurance_info')
                    ->maxLength(255)
                    ->default(null),

                Forms\Components\Textarea::make('training_certifications')->columnSpanFull(),

                Forms\Components\Select::make('vehicles')
                    ->label('Vehicles')
                    ->relationship('vehicles', 'name')
                    ->multiple()
                    ->preload(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('phone')->searchable(),
                Tables\Columns\TextColumn::make('license')->searchable(),
                Tables\Columns\TextColumn::make('license_expiration')->date()->sortable(),
                Tables\Columns\TextColumn::make('employment_status')->sortable(),
                Tables\Columns\IconColumn::make('medical_certified')->boolean(),
                Tables\Columns\TextColumn::make('performance_rating')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(true),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(true),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            TripsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDrivers::route('/'),
            'edit' => Pages\EditDriver::route('/{record}/edit'),
        ];
    }
}