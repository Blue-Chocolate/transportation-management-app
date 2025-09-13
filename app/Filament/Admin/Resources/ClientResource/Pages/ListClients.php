<?php

namespace App\Filament\Admin\Resources\ClientResource\Pages;

use App\Filament\Admin\Resources\ClientResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables;
use Filament\Forms;


class ListClients extends ListRecords
{
    protected static string $resource = ClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTableFilters(): array
    {
        return [
            Tables\Filters\Filter::make('created_at')
                ->form([
                    Forms\Components\DatePicker::make('created_from')->label('Created from'),
                    Forms\Components\DatePicker::make('created_until')->label('Created until'),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['created_from'],
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                        )
                        ->when(
                            $data['created_until'],
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                        );
                }),
            Tables\Filters\TernaryFilter::make('has_phone')
                ->label('Has Phone Number')
                ->boolean()
                ->placeholder('All Clients')
                ->trueLabel('Only With Phone')
                ->falseLabel('Only Without Phone')
                ->query(function (Builder $query, array $data): Builder {
                    return $query->when(
                        $data['value'] === true,
                        fn (Builder $query): Builder => $query->whereNotNull('phone')->where('phone', '!=', ''),
                        fn (Builder $query): Builder => $query->where(function ($q) {
                            $q->whereNull('phone')->orWhere('phone', '');
                        }),
                    );
                }),
            // Add more filters as needed, e.g., based on email domain or other fields
        ];
    }
}