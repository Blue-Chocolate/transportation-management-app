<?php

namespace App\Filament\Company\Resources;

use App\Filament\Company\Resources\ClientResource\Pages;
use App\Models\Client;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Clients';
    protected static ?string $modelLabel = 'Client';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label('Client Name'),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->unique(table: Client::class, ignoreRecord: true)
                    ->maxLength(255)
                    ->label('Email Address'),
                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->maxLength(20)
                    ->label('Phone Number'),
                Forms\Components\Hidden::make('company_id')
                    ->default(fn () => Auth::user()->company_id ?? abort(403, 'No company assigned to user.'))
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Client Name'),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->label('Email Address'),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->label('Phone Number'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Created At'),
            ])
            ->filters([
                // Add filters if needed, e.g., Tables\Filters\SelectFilter::make('status')
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $companyId = Auth::user()->company_id ?? abort(403, 'No company assigned to user.');
        return parent::getEloquentQuery()->where('company_id', $companyId);
    }
}