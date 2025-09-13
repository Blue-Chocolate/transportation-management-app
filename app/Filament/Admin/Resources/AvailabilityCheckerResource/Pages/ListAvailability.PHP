<?php

namespace App\Filament\Admin\Resources\AvailabilityCheckerResource\Pages;

use App\Filament\Admin\Resources\AvailabilityCheckerResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Trip;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ListAvailability extends ListRecords
{
    protected static string $resource = AvailabilityCheckerResource::class;

    public $start_time = null;
    public $end_time = null;
    public $name = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('check_availability')
                ->form([
                    Select::make('name')
                        ->label('Name')
                        ->placeholder('Search for a name (optional)')
                        ->searchable()
                        ->getSearchResultsUsing(function (string $search) {
                            $userId = \Illuminate\Support\Facades\Auth::id();

                            // Search driver names
                            $driverNames = Driver::where('user_id', $userId)
                                ->where('name', 'LIKE', "%{$search}%")
                                ->pluck('name', 'name')
                                ->toArray();

                            // Search vehicle names
                            $vehicleNames = Vehicle::where('user_id', $userId)
                                ->where('name', 'LIKE', "%{$search}%")
                                ->pluck('name', 'name')
                                ->toArray();

                            // Merge and unique
                            $merged = array_unique(array_merge($driverNames, $vehicleNames));

                            Log::info('Name search results', ['search' => $search, 'count' => count($merged)]);
                            return $merged;
                        })
                        ->getOptionLabelUsing(fn ($value) => $value)
                        ->nullable(), // Optional field

                    DateTimePicker::make('start_time')
                        ->required()
                        ->default(now()),

                    DateTimePicker::make('end_time')
                        ->required()
                        ->default(now()->addHours(1))
                        ->after('start_time'),
                ])
                ->action(function (array $data) {
                    Log::info('Check Availability submitted', ['data' => $data]);
                    $this->redirect(route('filament.admin.resources.availability-checkers.index', [
                        'start_time' => $data['start_time'],
                        'end_time' => $data['end_time'],
                        'name' => $data['name'] ?? null,
                    ]));
                }),
        ];
    }

    public function mount(): void
    {
        $this->start_time = request()->query('start_time');
        $this->end_time = request()->query('end_time');
        $this->name = request()->query('name');
        Log::info('ListAvailability mounted', [
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'name' => $this->name,
        ]);
        parent::mount();
    }

    public function getAvailableDrivers()
    {
        $start = $this->start_time ? Carbon::parse($this->start_time)->subMinutes(10) : null;
        $end = $this->end_time ? Carbon::parse($this->end_time)->addMinutes(10) : null;

        if (! $start || ! $end) {
            Log::info('getAvailableDrivers: No valid time range', ['start' => $this->start_time, 'end' => $this->end_time]);
            return collect([]);
        }

        $query = Driver::where('user_id', \Illuminate\Support\Facades\Auth::id());

        // Filter by name if provided (exact match since selected from autocomplete)
        if ($this->name) {
            $query->where('name', $this->name);
        }

        // Exclude drivers with overlapping trips within +/- 10 minutes
        $busyDriverIds = Trip::where('user_id', \Illuminate\Support\Facades\Auth::id())
            ->whereIn('status', ['planned', 'active'])
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_time', [$start, $end])
                  ->orWhereBetween('end_time', [$start, $end])
                  ->orWhere(function ($q) use ($start, $end) {
                      $q->where('start_time', '<=', $start)
                        ->where('end_time', '>=', $end);
                  });
            })
            ->pluck('driver_id')
            ->unique();

        $query->whereNotIn('id', $busyDriverIds);

        $drivers = $query->get();
        Log::info('getAvailableDrivers: Results', ['count' => $drivers->count(), 'name_filter' => $this->name]);
        return $drivers;
    }

    public function getAvailableVehicles()
    {
        $start = $this->start_time ? Carbon::parse($this->start_time)->subMinutes(10) : null;
        $end = $this->end_time ? Carbon::parse($this->end_time)->addMinutes(10) : null;

        if (! $start || ! $end) {
            Log::info('getAvailableVehicles: No valid time range', ['start' => $this->start_time, 'end' => $this->end_time]);
            return collect([]);
        }

        $query = Vehicle::where('user_id', \Illuminate\Support\Facades\Auth::id());

        // Filter by name if provided (exact match since selected from autocomplete)
        if ($this->name) {
            $query->where('name', $this->name);
        }

        // Exclude vehicles with overlapping trips within +/- 10 minutes
        $busyVehicleIds = Trip::where('user_id', \Illuminate\Support\Facades\Auth::id())
            ->whereIn('status', ['planned', 'active'])
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_time', [$start, $end])
                  ->orWhereBetween('end_time', [$start, $end])
                  ->orWhere(function ($q) use ($start, $end) {
                      $q->where('start_time', '<=', $start)
                        ->where('end_time', '>=', $end);
                  });
            })
            ->pluck('vehicle_id')
            ->unique();

        $query->whereNotIn('id', $busyVehicleIds);

        $vehicles = $query->get();
        Log::info('getAvailableVehicles: Results', ['count' => $vehicles->count(), 'name_filter' => $this->name]);
        return $vehicles;
    }
}