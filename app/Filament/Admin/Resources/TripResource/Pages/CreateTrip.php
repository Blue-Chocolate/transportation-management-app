<?php

namespace App\Filament\Admin\Resources\TripResource\Pages;

use App\Filament\Admin\Resources\TripResource;
use App\Models\Driver;
use App\Models\Trip;
use App\Models\Client;
use App\Models\Vehicle;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class CreateTrip extends CreateRecord
{
    protected static string $resource = TripResource::class;

    public function mount(): void
    {
        Log::info('CreateTrip: Form mounted');
        parent::mount();
    }

    public function hasForm(): bool
    {
        $hasForm = parent::hasForm();
        Log::info('CreateTrip: hasForm checked', ['has_form' => $hasForm]);
        return $hasForm;
    }

    public function getFormState(): array
    {
        $state = parent::getFormState();
        Log::info('CreateTrip: Form state retrieved', ['state_keys' => array_keys($state)]);
        return $state;
    }

  protected function mutateFormDataBeforeCreate(array $data): array
{
    Log::info('mutateFormDataBeforeCreate: Processing data', ['data' => $data]);

    $data['user_id'] = auth()->id();

    $start = Carbon::parse($data['start_time']);
    $end = Carbon::parse($data['end_time']);

    // 1️⃣ Validate end time
    if ($end->lte($start)) {
        throw ValidationException::withMessages([
            'end_time' => 'End time must be after start time.',
        ]);
    }
      if (Carbon::parse($data['end_time'])->lte(Carbon::parse($data['start_time']))) {
            throw ValidationException::withMessages([
                'end_time' => 'End time must be after start time.',
            ]);
        }
    // 2️⃣ Validate client, driver, vehicle existence
    $client = Client::where('user_id', $data['user_id'])->find($data['client_id']);
    $driver = Driver::where('user_id', $data['user_id'])->find($data['driver_id']);
    $vehicle = Vehicle::where('user_id', $data['user_id'])->find($data['vehicle_id']);

    if (! $client) {
        throw ValidationException::withMessages(['client_id' => 'Invalid client.']);
    }
    if (! $driver) {
        throw ValidationException::withMessages(['driver_id' => 'Invalid driver.']);
    }
    if (! $vehicle) {
        throw ValidationException::withMessages(['vehicle_id' => 'Invalid vehicle.']);
    }

    // 3️⃣ Check driver-vehicle assignment
    if (! $driver->vehicles()->where('vehicles.id', $vehicle->id)->exists()) {
        throw ValidationException::withMessages(['vehicle_id' => 'Vehicle not assigned to driver.']);
    }

    $data['vehicle_type'] = $vehicle->vehicle_type;

    // 4️⃣ Check overlapping trips
    $conflict = Trip::where('user_id', $data['user_id'])
        ->where(function ($q) use ($driver, $vehicle) {
            $q->where('driver_id', $driver->id)
              ->orWhere('vehicle_id', $vehicle->id);
        })
        ->where(function ($q) use ($start, $end) {
            $q->where(function ($q2) use ($start, $end) {
                $q2->where('start_time', '<', $end)
                   ->where('end_time', '>', $start);
            });
        })
        ->exists();

    if ($conflict) {
        throw ValidationException::withMessages([
            'start_time' => 'Driver or vehicle already booked in this time range.',
        ]);
    }

    // 5️⃣ Validate status
    if (! in_array($data['status'], array_column(\App\Enums\TripStatus::cases(), 'value'))) {
        throw ValidationException::withMessages(['status' => 'Invalid trip status.']);
    }

    Log::info('mutateFormDataBeforeCreate: Data validated successfully', ['data' => $data]);
    return $data;
}



    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        Log::info('handleRecordCreation: Attempting to create trip with Tinker-like data', ['data' => $data]);
        try {
            $record = static::getModel()::create($data);
            Log::info('handleRecordCreation: Trip created successfully (matches Tinker)', ['id' => $record->id, 'data' => $record->toArray()]);
            return $record;
        } catch (\Throwable $e) {
            Log::error('handleRecordCreation: Failed to create trip (DB/Model error)', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data
            ]);
            throw $e;
        }
    }
}