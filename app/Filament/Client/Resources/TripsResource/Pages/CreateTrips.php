<?php

namespace App\Filament\Client\Resources\TripsResource\Pages;

use App\Filament\Client\Resources\TripsResource;
use App\Models\Driver;
use App\Models\Trip;
use App\Models\Vehicle;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateTrips extends CreateRecord
{
    protected static string $resource = TripsResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['client_id'] = auth('client')->id();
        $clientUserId = auth('client')->user()->user_id;

        // Parse dates
        $start = \Carbon\Carbon::parse($data['start_time']);
        $end = \Carbon\Carbon::parse($data['end_time']);
        $now = \Carbon\Carbon::now();

        // Validate start_time: not more than 1 minute in the past
        if ($start->isBefore($now->subMinute(1))) {
            throw ValidationException::withMessages([
                'start_time' => 'The start time cannot be more than 1 minute in the past.',
            ]);
        }

        // Validate start_time: not more than 3 weeks in the future
        if ($start->isAfter($now->addWeeks(3))) {
            throw ValidationException::withMessages([
                'start_time' => 'The start time cannot be more than 3 weeks in the future.',
            ]);
        }

        // Validate driver belongs to client's user_id
        $driver = Driver::find($data['driver_id']);
        if (!$driver || $driver->user_id !== $clientUserId) {
            throw ValidationException::withMessages([
                'driver_id' => 'The selected driver is not available for your organization.',
            ]);
        }

        // Validate vehicle is assigned to the selected driver
        $vehicle = Vehicle::find($data['vehicle_id']);
        if (!$vehicle) {
            throw ValidationException::withMessages([
                'vehicle_id' => 'The selected vehicle is not valid.',
            ]);
        }

        // Check if the vehicle is assigned to the driver through pivot table
        $isVehicleAssignedToDriver = $driver->vehicles()->where('vehicle_id', $data['vehicle_id'])->exists();
        if (!$isVehicleAssignedToDriver) {
            throw ValidationException::withMessages([
                'vehicle_id' => 'The selected vehicle is not assigned to the selected driver.',
            ]);
        }

        // Ensure no overlap for driver
        $driverConflict = Trip::where('driver_id', $data['driver_id'])
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_time', [$start, $end])
                  ->orWhereBetween('end_time', [$start, $end])
                  ->orWhere(function ($q2) use ($start, $end) {
                      $q2->where('start_time', '<=', $start)
                         ->where('end_time', '>=', $end);
                  });
            })
            ->exists();

        if ($driverConflict) {
            throw ValidationException::withMessages([
                'driver_id' => 'The selected driver is already booked during this time period.',
            ]);
        }

        // Ensure no overlap for vehicle
        $vehicleConflict = Trip::where('vehicle_id', $data['vehicle_id'])
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_time', [$start, $end])
                  ->orWhereBetween('end_time', [$start, $end])
                  ->orWhere(function ($q2) use ($start, $end) {
                      $q2->where('start_time', '<=', $start)
                         ->where('end_time', '>=', $end);
                  });
            })
            ->exists();

        if ($vehicleConflict) {
            throw ValidationException::withMessages([
                'vehicle_id' => 'The selected vehicle is already booked during this time period.',
            ]);
        }

        return $data;
    }
}