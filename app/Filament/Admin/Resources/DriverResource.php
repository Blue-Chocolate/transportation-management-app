<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\DriverResource\Pages;
use App\Models\Driver;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class DriverResource extends Resource
{
    protected static ?string $model = Driver::class;
    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static ?string $navigationGroup = 'Operations';

    // --------------------------------------
    // Form Schema
    // --------------------------------------
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')->required(),
                Forms\Components\TextInput::make('phone'),
                Forms\Components\TextInput::make('email')->email(),
                Forms\Components\TextInput::make('emergency_contact'),
                Forms\Components\TextInput::make('license'),
                Forms\Components\DatePicker::make('license_expiration'),
                Forms\Components\DatePicker::make('date_of_birth'),
                Forms\Components\TextInput::make('address'),
                Forms\Components\DatePicker::make('hire_date'),
                Forms\Components\Select::make('employment_status')
                    ->options(['active' => 'Active', 'inactive' => 'Inactive'])
                    ->default('active'),
                Forms\Components\Textarea::make('notes'),
                Forms\Components\FileUpload::make('profile_photo')->image(),
                Forms\Components\TextInput::make('insurance_info'),
                Forms\Components\Repeater::make('training_certifications')
                    ->schema([Forms\Components\TextInput::make('certification')])
                    ->nullable(),
                Forms\Components\Toggle::make('medical_certified'),
                Forms\Components\DatePicker::make('background_check_date'),
                Forms\Components\TextInput::make('performance_rating')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(5),
                Forms\Components\Repeater::make('route_assignments')
                    ->schema([Forms\Components\TextInput::make('route')])
                    ->nullable(),
            ]);
    }

    // --------------------------------------
    // Table Schema
    // --------------------------------------
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone'),
                Tables\Columns\TextColumn::make('email'),
                Tables\Columns\TextColumn::make('license'),
                Tables\Columns\TextColumn::make('employment_status')
                    ->badge()
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('employment_status')
                    ->options(['active' => 'Active', 'inactive' => 'Inactive']),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    // --------------------------------------
    // Infolist Schema
    // --------------------------------------
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Profile')
                    ->columns(2)
                    ->schema([
                        ImageEntry::make('profile_photo')->circular()->height(120),
                        TextEntry::make('name'),
                        TextEntry::make('email'),
                        TextEntry::make('phone'),
                        TextEntry::make('emergency_contact'),
                    ]),
                Section::make('Driver Details')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('license'),
                        TextEntry::make('license_expiration')->date(),
                        TextEntry::make('date_of_birth')->date(),
                        TextEntry::make('address'),
                        TextEntry::make('hire_date')->date(),
                        TextEntry::make('employment_status')->badge(),
                    ]),
                Section::make('Operational Data')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('vehicles.name')
                            ->listWithLineBreaks()
                            ->getStateUsing(fn ($record) =>
                                $record->vehicles->pluck('name')->toArray()
                            ),
                        TextEntry::make('route_assignments')->listWithLineBreaks(),
                        TextEntry::make('performance_rating')->suffix('/5.00'),
                        IconEntry::make('medical_certified')
                            ->boolean()
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-x-circle')
                            ->trueColor('success')
                            ->falseColor('danger'),
                        TextEntry::make('background_check_date')->date(),
                    ]),
                Section::make('Additional Info')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('insurance_info'),
                        TextEntry::make('training_certifications')->listWithLineBreaks(),
                        TextEntry::make('notes')->markdown(),
                    ]),
            ]);
    }

    // --------------------------------------
    // Relations
    // --------------------------------------
    public static function getRelations(): array
    {
        return [
            // Add VehiclesRelationManager if defined
        ];
    }

    // --------------------------------------
    // Eloquent Query Optimization + Redis Caching
    // --------------------------------------
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $userId = $user ? $user->id : 0;

        $driverIds = Cache::store('redis')->remember("driver_ids_user_{$userId}", now()->addSeconds(30), function () use ($userId) {
            return Driver::where('user_id', $userId)->pluck('id')->toArray();
        });

        if (empty($driverIds)) {
            return Driver::query()->whereRaw('1 = 0');
        }

        return Driver::query()
            ->with(['vehicles'])
            ->whereIn('id', $driverIds)
            ->orderBy('name', 'asc');
    }

    // --------------------------------------
    // Auto-fill user_id
    // --------------------------------------
    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->user()->id;
        return $data;
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        $data['user_id'] = auth()->user()->id;
        return $data;
    }

    // --------------------------------------
    // Pages
    // --------------------------------------
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDrivers::route('/'),
            'view' => Pages\ViewDriver::route('/{record}'),
            'edit' => Pages\EditDriver::route('/{record}/edit'),
        ];
    }
}