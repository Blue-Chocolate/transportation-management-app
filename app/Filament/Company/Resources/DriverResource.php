<?php

namespace App\Filament\Company\Resources;

use App\Filament\Company\Resources\DriverResource\Pages;
use App\Models\Driver;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class DriverResource extends Resource
{
    protected static ?string $model = Driver::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Drivers';
    protected static ?string $modelLabel = 'Driver';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')->required()->maxLength(255),
                Forms\Components\TextInput::make('email')->email()->unique(ignoreRecord: true)->maxLength(255),
                Forms\Components\TextInput::make('phone')->tel()->maxLength(20),
                Forms\Components\TextInput::make('license')->required()->maxLength(50),
                Forms\Components\DatePicker::make('license_expiration')->required(),
                Forms\Components\Hidden::make('company_id')->default(fn () => Auth::user()->company_id),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('license')->searchable(),
                Tables\Columns\TextColumn::make('license_expiration')->date()->sortable(),
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
            'index' => Pages\ListDrivers::route('/'),
            'create' => Pages\CreateDriver::route('/create'),
            'edit' => Pages\EditDriver::route('/{record}/edit'),
        ];
    }

   public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', Auth::user()->company_id);
    }
}
