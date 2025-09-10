<?php

namespace App\Filament\Admin\Resources;

use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Trip;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class AvailabilityChecker extends Page implements Forms\Contracts\HasForms, Tables\Contracts\HasTable
{
    use Forms\Concerns\InteractsWithForms;
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationGroup = 'Operations';
    protected static string $view = 'filament.pages.availability-checker';

    public ?string $start_time = null;
    public ?string $end_time = null;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DateTimePicker::make('start_time')
                    ->label('Start Time')
                    ->native(false)
                    ->required(),

                Forms\Components\DateTimePicker::make('end_time')
                    ->label('End Time')
                    ->after('start_time')
                    ->native(false)
                    ->required(),
            ])
            ->statePath('form');
    }

    public function submit(): void
    {
        $data = $this->form->getState();
        $this->start_time = $data['start_time'];
        $this->end_time = $data['end_time'];
    }

    public function getAvailableDrivers(): Collection
    {
        if (! $this->start_time || ! $this->end_time) return collect();

        $start = Carbon::parse($this->start_time);
        $end   = Carbon::parse($this->end_time);

        $busyDrivers = Trip::where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->pluck('driver_id');

        return Driver::whereNotIn('id', $busyDrivers)->get();
    }

    public function getAvailableVehicles(): Collection
    {
        if (! $this->start_time || ! $this->end_time) return collect();

        $start = Carbon::parse($this->start_time);
        $end   = Carbon::parse($this->end_time);

        $busyVehicles = Trip::where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->pluck('vehicle_id');

        return Vehicle::whereNotIn('id', $busyVehicles)->get();
    }
}
