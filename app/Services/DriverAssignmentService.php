<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\Trip;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class DriverAssignmentService
{
    /**
     * Assign an available driver and vehicle for a trip.
     *
     * @param string $vehicleType
     * @param string $startTime
     * @param string $endTime
     * @return array|null  ['driver_id' => int, 'vehicle_id' => int]
     * @throws ValidationException
     */
    public static function assignDriver(string $vehicleType, string $startTime, string $endTime): ?array
    {
        // Optional: Use caching for performance (5 minutes)
        $cacheKey = "available_drivers_{$vehicleType}_{$startTime}_{$endTime}";
        return Cache::remember($cacheKey, 300, function () use ($vehicleType, $startTime, $endTime) {

            // Eager-load trips overlapping with requested period
            $drivers = Driver::whereHas('vehicles', fn($q) => $q->where('type', $vehicleType))
                ->with([
                    'vehicles' => fn($q) => $q->where('type', $vehicleType),
                    'trips' => fn($q) => $q->where(function ($query) use ($startTime, $endTime) {
                        $query->whereBetween('start_time', [$startTime, $endTime])
                              ->orWhereBetween('end_time', [$startTime, $endTime])
                              ->orWhere(fn($q2) => $q2->where('start_time', '<=', $startTime)
                                                       ->where('end_time', '>=', $endTime));
                    })
                ])
                ->get();

            foreach ($drivers as $driver) {
                if ($driver->trips->isNotEmpty()) {
                    continue; // driver is busy
                }

                // Find an available vehicle
                $vehicle = $driver->vehicles->first(fn($v) => $v->trips()
                    ->where(function ($q) use ($startTime, $endTime) {
                        $q->whereBetween('start_time', [$startTime, $endTime])
                          ->orWhereBetween('end_time', [$startTime, $endTime])
                          ->orWhere(fn($q2) => $q2->where('start_time', '<=', $startTime)
                                                   ->where('end_time', '>=', $endTime));
                    })->doesntExist()
                );

                if ($vehicle) {
                    // Return IDs instead of mutating the model
                    return [
                        'driver_id' => $driver->id,
                        'vehicle_id' => $vehicle->id,
                    ];
                }
            }

            // No available driver found
            return null;
        });
    }

    /**
     * Create a trip with assigned driver and vehicle within a transaction.
     *
     * @param array $tripData ['vehicle_type', 'start_time', 'end_time', ...]
     * @return Trip
     * @throws ValidationException
     */
    public static function createTrip(array $tripData): Trip
    {
        return DB::transaction(function () use ($tripData) {
            $assignment = self::assignDriver(
                $tripData['vehicle_type'],
                $tripData['start_time'],
                $tripData['end_time']
            );

            if (!$assignment) {
                throw ValidationException::withMessages([
                    'driver' => 'No available driver or vehicle for the selected time and type.',
                ]);
            }

            $trip = Trip::create(array_merge($tripData, [
                'driver_id' => $assignment['driver_id'],
                'vehicle_id' => $assignment['vehicle_id'],
            ]));

            return $trip;
        });
    }
}
