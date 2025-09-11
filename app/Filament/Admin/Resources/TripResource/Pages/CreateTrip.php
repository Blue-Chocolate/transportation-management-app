<?php

namespace App\Filament\Admin\Resources\TripResource\Pages;

use App\Filament\Admin\Resources\TripResource;
use App\Models\Driver;
use App\Models\Trip;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;
use Throwable;

class CreateTrip extends CreateRecord
{
    protected static string $resource = TripResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        try {
            $start = Carbon::parse($data['start_time']);
            $end = Carbon::parse($data['end_time']);

            if ($end->lte($start)) {
                throw ValidationException::withMessages([
                    'end_time' => 'End time must be after start time.',
                ]);
            }

            // Check if driver exists
            $driver = Driver::find($data['driver_id']);
            if (! $driver) {
                throw ValidationException::withMessages([
                    'driver_id' => 'Selected driver does not exist.',
                ]);
            }

            // Check vehicle belongs to driver
            if (! $driver->vehicles()->where('vehicle_id', $data['vehicle_id'])->exists()) {
                throw ValidationException::withMessages([
                    'vehicle_id' => 'This vehicle is not assigned to the selected driver.',
                ]);
            }

            // Check overlap conflicts for driver or vehicle
            $conflict = Trip::where(function ($q) use ($data) {
                    $q->where('driver_id', $data['driver_id'])
                      ->orWhere('vehicle_id', $data['vehicle_id']);
                })
                ->where('start_time', '<', $end)
                ->where('end_time', '>', $start)
                ->exists();

            if ($conflict) {
                throw ValidationException::withMessages([
                    'start_time' => 'Driver or vehicle already booked in this time range. Please choose a different time, driver, or vehicle.',
                ]);
            }

            return $data;
        } catch (ValidationException $e) {
            // Filament will handle ValidationException and display field-specific errors
            throw $e;
        } catch (Throwable $e) {
            // Catch any unexpected errors and notify the admin
            Notification::make()
                ->title('Error Creating Trip')
                ->danger()
                ->body('An unexpected error occurred: ' . $e->getMessage())
                ->persistent()
                ->send();

            // Log the error (optional, if you have logging set up)
            // \Log::error('Trip creation error: ' . $e->getMessage());

            // Rethrow to prevent creation
            throw ValidationException::withMessages([
                'general' => 'An unexpected error occurred. Please try again or contact support.',
            ]);
        }
    }
}