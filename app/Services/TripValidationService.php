<?php

namespace App\Services;

use App\Models\{Driver, Vehicle, Client, Trip};
use App\Enums\{TripStatus, EmploymentStatus};
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TripValidationService
{
    public function validateAndEnrich(array $data): array
    {
        Log::info('TripValidationService: Starting validation', ['data' => $data]);
        
        try {
            // Run validations in logical order
            $this->validateUser($data);
            Log::info('TripValidationService: User validation passed');
            
            $dates = $this->validateDates($data);
            Log::info('TripValidationService: Date validation passed', ['dates' => $dates]);
            
            $resources = $this->validateResources($data);
            Log::info('TripValidationService: Resource validation passed');
            
            $this->validateDriverVehicleAssignment($resources['driver'], $resources['vehicle']);
            Log::info('TripValidationService: Driver-vehicle assignment validation passed');
            
            $this->validateOverlaps($data, $dates['start'], $dates['end']);
            Log::info('TripValidationService: Overlap validation passed');
            
            $this->validateStatus($data);
            Log::info('TripValidationService: Status validation passed');

            $enrichedData = $this->enrichData($data, $resources);
            Log::info('TripValidationService: Data enrichment completed', ['enriched_data' => $enrichedData]);
            
            return $enrichedData;
            
        } catch (\Exception $e) {
            Log::error('TripValidationService: Validation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    private function validateUser(array $data): void
    {
        if (empty($data['user_id'])) {
            Log::warning('TripValidationService: User validation failed - no user_id');
            throw ValidationException::withMessages([
                'user_id' => 'User must be authenticated to create a trip.',
            ]);
        }
    }

    private function validateDates(array $data): array
    {
        try {
            $start = Carbon::parse($data['start_time']);
            $end = Carbon::parse($data['end_time']);
        } catch (\Exception $e) {
            Log::error('TripValidationService: Date parsing failed', [
                'data' => $data, 
                'error' => $e->getMessage()
            ]);
            throw ValidationException::withMessages([
                'start_time' => 'Invalid date format.',
            ]);
        }

        // Validate logical date relationship
        if ($end->lte($start)) {
            Log::warning('TripValidationService: End time is not after start time', [
                'start' => $start,
                'end' => $end
            ]);
            throw ValidationException::withMessages([
                'end_time' => 'End time must be after start time.',
            ]);
        }

        // Business rule: No past trips (with 5-minute grace period)
        if ($start->lt(now()->subMinutes(5))) {
            Log::warning('TripValidationService: Trip scheduled in the past', ['start_time' => $start]);
            throw ValidationException::withMessages([
                'start_time' => 'Trip cannot be scheduled in the past.',
            ]);
        }

        // Business rule: Maximum trip duration (24 hours)
        if ($end->diffInHours($start) > 24) {
            Log::warning('TripValidationService: Trip duration exceeds 24 hours', [
                'duration_hours' => $end->diffInHours($start)
            ]);
            throw ValidationException::withMessages([
                'end_time' => 'Trip duration cannot exceed 24 hours.',
            ]);
        }

        return ['start' => $start, 'end' => $end];
    }

    private function validateResources(array $data): array
    {
        $userId = $data['user_id'];

        Log::info('TripValidationService: Validating resources', [
            'user_id' => $userId,
            'client_id' => $data['client_id'] ?? null,
            'driver_id' => $data['driver_id'] ?? null,
            'vehicle_id' => $data['vehicle_id'] ?? null,
        ]);

        $client = Client::where('user_id', $userId)->find($data['client_id']);
        $driver = Driver::where('user_id', $userId)->find($data['driver_id']);
        $vehicle = Vehicle::where('user_id', $userId)->find($data['vehicle_id']);

        $errors = [];
        if (!$client) {
            $errors['client_id'] = 'Invalid client selected or client does not belong to your account.';
            Log::warning('TripValidationService: Client not found', ['client_id' => $data['client_id']]);
        }
        if (!$driver) {
            $errors['driver_id'] = 'Invalid driver selected or driver does not belong to your account.';
            Log::warning('TripValidationService: Driver not found', ['driver_id' => $data['driver_id']]);
        }
        if (!$vehicle) {
            $errors['vehicle_id'] = 'Invalid vehicle selected or vehicle does not belong to your account.';
            Log::warning('TripValidationService: Vehicle not found', ['vehicle_id' => $data['vehicle_id']]);
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        // Validate driver employment status
        Log::info('TripValidationService: Checking driver employment status', [
            'driver_id' => $driver->id,
            'employment_status' => $driver->employment_status,
            'employment_status_type' => is_object($driver->employment_status) ? get_class($driver->employment_status) : gettype($driver->employment_status)
        ]);

        if (!$this->isDriverActive($driver)) {
            $statusValue = $this->getEmploymentStatusValue($driver->employment_status);
            
            Log::warning('TripValidationService: Driver employment status check failed', [
                'driver_id' => $driver->id,
                'status' => $statusValue,
                'raw_status' => $driver->employment_status
            ]);
            
            throw ValidationException::withMessages([
                'driver_id' => 'Selected driver is not currently active. Status: ' . $statusValue,
            ]);
        }

        return compact('client', 'driver', 'vehicle');
    }

    private function isDriverActive(Driver $driver): bool
    {
        $status = $driver->employment_status;
        
        Log::info('TripValidationService: Checking if driver is active', [
            'status' => $status,
            'is_enum' => $status instanceof EmploymentStatus,
            'status_type' => is_object($status) ? get_class($status) : gettype($status)
        ]);
        
        // Handle enum case
        if ($status instanceof EmploymentStatus) {
            return $status === EmploymentStatus::ACTIVE;
        }
        
        // Handle string case (fallback)
        return $status === 'active';
    }

    private function getEmploymentStatusValue($status): string
    {
        if ($status instanceof EmploymentStatus) {
            return $status->value;
        }
        
        return (string) $status;
    }

    private function validateDriverVehicleAssignment(Driver $driver, Vehicle $vehicle): void
    {
        Log::info('TripValidationService: Validating driver-vehicle assignment', [
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id
        ]);

        if (!$driver->vehicles()->where('vehicles.id', $vehicle->id)->exists()) {
            Log::warning('TripValidationService: Driver-vehicle assignment failed', [
                'driver_id' => $driver->id,
                'vehicle_id' => $vehicle->id,
                'assigned_vehicles' => $driver->vehicles()->pluck('vehicles.id')->toArray()
            ]);
            
            throw ValidationException::withMessages([
                'vehicle_id' => 'The selected vehicle is not assigned to the selected driver.',
            ]);
        }
    }

    private function validateOverlaps(array $data, Carbon $start, Carbon $end): void
    {
        Log::info('TripValidationService: Starting overlap validation', [
            'start' => $start,
            'end' => $end,
            'driver_id' => $data['driver_id'],
            'vehicle_id' => $data['vehicle_id']
        ]);

        // Skip the record if we're updating (record has ID)
        $query = Trip::where('user_id', $data['user_id'])
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->whereNotIn('status', [TripStatus::CANCELLED->value]);

        // Exclude current trip if updating
        if (!empty($data['id'])) {
            $query->where('id', '!=', $data['id']);
        }

        $conflicts = $query->where(function($query) use ($data) {
            $query->where('driver_id', $data['driver_id'])
                  ->orWhere('vehicle_id', $data['vehicle_id']);
        })
        ->select('driver_id', 'vehicle_id', 'start_time', 'end_time', 'id', 'status')
        ->get();

        Log::info('TripValidationService: Found potential conflicts', [
            'conflict_count' => $conflicts->count(),
            'conflicts' => $conflicts->toArray()
        ]);

        $errors = [];
        foreach ($conflicts as $conflict) {
            if ($conflict->driver_id == $data['driver_id']) {
                $errors['driver_id'] = "Driver has a conflicting trip scheduled from {$conflict->start_time->format('Y-m-d H:i')} to {$conflict->end_time->format('Y-m-d H:i')}.";
            }
            if ($conflict->vehicle_id == $data['vehicle_id']) {
                $errors['vehicle_id'] = "Vehicle has a conflicting trip scheduled from {$conflict->start_time->format('Y-m-d H:i')} to {$conflict->end_time->format('Y-m-d H:i')}.";
            }
        }

        if (!empty($errors)) {
            Log::warning('TripValidationService: Overlap conflicts found', ['errors' => $errors]);
            throw ValidationException::withMessages($errors);
        }

        Log::info('TripValidationService: Overlap validation completed successfully', [
            'conflicts_checked' => $conflicts->count(),
            'trip_period' => ['start' => $start, 'end' => $end]
        ]);
    }

    private function validateStatus(array $data): void
    {
        // If status is not provided, skip validation (will use default)
        if (!isset($data['status'])) {
            Log::info('TripValidationService: No status provided, skipping status validation');
            return;
        }

        Log::info('TripValidationService: Validating status', ['status' => $data['status']]);

        $validStatuses = array_column(TripStatus::cases(), 'value');
        
        if (!in_array($data['status'], $validStatuses)) {
            Log::warning('TripValidationService: Invalid status provided', [
                'provided_status' => $data['status'],
                'valid_statuses' => $validStatuses
            ]);
            throw ValidationException::withMessages([
                'status' => 'Invalid trip status selected.',
            ]);
        }

        // Business rules for status on new trips (not updates)
        if (empty($data['id'])) {
            $restrictedStatuses = [TripStatus::COMPLETED->value, TripStatus::ACTIVE->value];
            if (in_array($data['status'], $restrictedStatuses)) {
                Log::warning('TripValidationService: Restricted status for new trip', [
                    'status' => $data['status'],
                    'restricted_statuses' => $restrictedStatuses
                ]);
                throw ValidationException::withMessages([
                    'status' => 'New trips can only be created with PLANNED or CANCELLED status.',
                ]);
            }
        }
    }

    private function enrichData(array $data, array $resources): array
    {
        Log::info('TripValidationService: Starting data enrichment');

        // Auto-populate vehicle type from selected vehicle if it exists
        if (isset($resources['vehicle']->vehicle_type)) {
            $data['vehicle_type'] = $resources['vehicle']->vehicle_type;
            Log::info('TripValidationService: Vehicle type populated', ['vehicle_type' => $data['vehicle_type']]);
        }

        // Generate description if not provided
        if (empty($data['description'])) {
            $duration = Carbon::parse($data['end_time'])->diffInHours(Carbon::parse($data['start_time']));
            $data['description'] = "Trip for {$resources['client']->name} with driver {$resources['driver']->name} ({$duration}h duration)";
            Log::info('TripValidationService: Description generated', ['description' => $data['description']]);
        }

        return $data;
    }
}