<?php

namespace App\Filament\Client\Resources\ClientResource\RelationManagers;

use App\Enums\TripStatus;
use App\Models\Driver;
use App\Models\Trip;
use App\Models\Vehicle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TripsRelationManager extends RelationManager
{
    protected static string $relationship = 'trips';

    protected static ?string $recordTitleAttribute = 'description';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('company_id')
                    ->relationship('company', 'name')
                    ->default(fn ($livewire) => $livewire->ownerRecord->company_id)
                    ->disabled()
                    ->required(),
                Forms\Components\Select::make('driver_id')
                    ->relationship('driver', 'name', fn (Builder $query) => 
                        $query->where('company_id', $form->getRecord()->company_id ?? $form->getLivewire()->ownerRecord->company_id)
                              ->where('employment_status', \App\Enums\EmploymentStatus::ACTIVE))
                    ->required()
                    ->preload(),
                Forms\Components\Select::make('vehicle_id')
                    ->relationship('vehicle', 'name', fn (Builder $query) => 
                        $query->where('company_id', $form->getRecord()->company_id ?? $form->getLivewire()->ownerRecord->company_id))
                    ->required()
                    ->preload(),
                Forms\Components\DateTimePicker::make('start_time')
                    ->required()
                    ->afterOrEqual(now()),
                Forms\Components\DateTimePicker::make('end_time')
                    ->required()
                    ->after('start_time'),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Forms\Components\Select::make('status')
                    ->options(TripStatus::class)
                    ->default(TripStatus::PLANNED)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('driver.name')
                    ->label('Driver')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('vehicle.name')
                    ->label('Vehicle')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_time')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_time')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50),
                Tables\Columns\BadgeColumn::make('status')
                    ->formatStateUsing(fn ($state) => $state->getLabel())
                    ->colors([
                        'primary' => TripStatus::PLANNED->value,
                        'warning' => TripStatus::ACTIVE->value,
                        'success' => TripStatus::COMPLETED->value,
                        'danger' => TripStatus::CANCELLED->value,
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(TripStatus::class)
                    ->default(TripStatus::PLANNED->value),
                Tables\Filters\Filter::make('active_trips')
                    ->query(fn (Builder $query) => 
                        $query->where('start_time', '<=', now())
                              ->where('end_time', '>=', now())
                              ->where('status', TripStatus::ACTIVE)),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->before(function ($data, $action) {
                        // Validate driver-vehicle assignment
                        $driver = Driver::find($data['driver_id']);
                        if (!$driver || !$driver->vehicles()->where('vehicle_id', $data['vehicle_id'])->exists()) {
                            throw ValidationException::withMessages([
                                'vehicle_id' => 'This vehicle is not assigned to the selected driver.',
                            ]);
                        }

                        // Validate no overlaps
                        $start = \Carbon\Carbon::parse($data['start_time']);
                        $end = \Carbon\Carbon::parse($data['end_time']);
                        $conflict = Trip::where(function ($q) use ($data, $start, $end) {
                            $q->where('driver_id', $data['driver_id'])
                              ->orWhere('vehicle_id', $data['vehicle_id']);
                        })
                        ->where('id', '!=', $data['id'] ?? null)
                        ->where(function ($q) use ($start, $end) {
                            $q->whereBetween('start_time', [$start, $end])
                              ->orWhereBetween('end_time', [$start, $end])
                              ->orWhere(function ($q2) use ($start, $end) {
                                  $q2->where('start_time', '<=', $start)
                                     ->where('end_time', '>=', $end);
                              });
                        })
                        ->exists();

                        if ($conflict) {
                            throw ValidationException::withMessages([
                                'start_time' => 'Driver or vehicle already booked in this time range.',
                            ]);
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('start_time', 'desc')
            ->query(fn ($query) => $query->with(['driver', 'vehicle'])); // Eager load
    }
}