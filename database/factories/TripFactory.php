<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\{Client, Driver, Vehicle, User, Trip};
use App\Enums\TripStatus;
use Carbon\Carbon;

class TripFactory extends Factory
{
    // Static counters to ensure non-overlapping times
    private static $lastDriverTime = [];
    private static $lastVehicleTime = [];

    public function definition(): array
    {
        // Create a base time for this trip
        $baseTime = Carbon::now()->addDays($this->faker->numberBetween(1, 30));
        $duration = $this->faker->numberBetween(2, 8); // 2-8 hours
        
        return [
            'client_id'     => Client::factory(),
            'driver_id'     => Driver::factory(),
            'vehicle_id'    => Vehicle::factory(),
            'user_id'       => User::factory(),
            'vehicle_type'  => $this->faker->randomElement(['car', 'van', 'truck', 'bus']),
            'start_time'    => $baseTime,
            'end_time'      => $baseTime->copy()->addHours($duration),
            'description'   => $this->faker->sentence(6),
            'status'        => $this->faker->randomElement([
                TripStatus::PLANNED->value,
                TripStatus::ACTIVE->value,
                TripStatus::COMPLETED->value
            ]),
        ];
    }

    public function forUser($userId): static
    {
        return $this->state(fn () => ['user_id' => $userId]);
    }

    public function withDriver($driverId): static
    {
        return $this->state(function () use ($driverId) {
            // Ensure non-overlapping times for this driver
            $startTime = $this->getNextAvailableTime('driver', $driverId);
            $duration = $this->faker->numberBetween(2, 6);
            
            return [
                'driver_id' => $driverId,
                'start_time' => $startTime,
                'end_time' => $startTime->copy()->addHours($duration),
            ];
        });
    }

    public function withVehicle($vehicleId): static
    {
        return $this->state(function () use ($vehicleId) {
            // Ensure non-overlapping times for this vehicle
            $startTime = $this->getNextAvailableTime('vehicle', $vehicleId);
            $duration = $this->faker->numberBetween(2, 6);
            
            return [
                'vehicle_id' => $vehicleId,
                'start_time' => $startTime,
                'end_time' => $startTime->copy()->addHours($duration),
            ];
        });
    }

    public function withClient($clientId): static
    {
        return $this->state(fn () => ['client_id' => $clientId]);
    }

    public function planned(): static
    {
        return $this->state(fn () => [
            'status' => TripStatus::PLANNED->value,
            'start_time' => Carbon::now()->addDays($this->faker->numberBetween(1, 7)),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => TripStatus::COMPLETED->value,
            'start_time' => Carbon::now()->subDays($this->faker->numberBetween(1, 30)),
        ]);
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'status' => TripStatus::ACTIVE->value,
            'start_time' => Carbon::now()->subHours($this->faker->numberBetween(1, 4)),
        ]);
    }

    /**
     * Get next available time for driver or vehicle to prevent overlaps
     */
    private function getNextAvailableTime(string $type, $resourceId): Carbon
    {
        $key = $type . '_' . $resourceId;
        
        if (!isset(self::${$type === 'driver' ? 'lastDriverTime' : 'lastVehicleTime'}[$key])) {
            // First trip for this resource - start from now + 1 day
            $startTime = Carbon::now()->addDay()->setHour(8)->setMinute(0)->setSecond(0);
        } else {
            // Start after the last trip ended + buffer time
            $lastEndTime = self::${$type === 'driver' ? 'lastDriverTime' : 'lastVehicleTime'}[$key];
            $startTime = $lastEndTime->copy()->addHours($this->faker->numberBetween(2, 12)); // 2-12 hour gap
        }
        
        // Store the end time for next trip
        $duration = $this->faker->numberBetween(2, 8);
        $endTime = $startTime->copy()->addHours($duration);
        
        if ($type === 'driver') {
            self::$lastDriverTime[$key] = $endTime;
        } else {
            self::$lastVehicleTime[$key] = $endTime;
        }
        
        return $startTime;
    }

    /**
     * Create non-overlapping trips for specific driver and vehicle combination
     */
    public function nonOverlapping($driverId, $vehicleId): static
    {
        return $this->state(function () use ($driverId, $vehicleId) {
            // Get the latest end time for both driver and vehicle
            $driverKey = 'driver_' . $driverId;
            $vehicleKey = 'vehicle_' . $vehicleId;
            
            $driverLastTime = self::$lastDriverTime[$driverKey] ?? Carbon::now()->addDay();
            $vehicleLastTime = self::$lastVehicleTime[$vehicleKey] ?? Carbon::now()->addDay();
            
            // Start after both are available
            $startTime = $driverLastTime->gt($vehicleLastTime) 
                ? $driverLastTime->copy()->addHours(2)
                : $vehicleLastTime->copy()->addHours(2);
            
            $duration = $this->faker->numberBetween(2, 6);
            $endTime = $startTime->copy()->addHours($duration);
            
            // Update both tracking arrays
            self::$lastDriverTime[$driverKey] = $endTime;
            self::$lastVehicleTime[$vehicleKey] = $endTime;
            
            return [
                'driver_id' => $driverId,
                'vehicle_id' => $vehicleId,
                'start_time' => $startTime,
                'end_time' => $endTime,
            ];
        });
    }

    /**
     * Reset time tracking (useful for tests)
     */
    public static function resetTimeTracking(): void
    {
        self::$lastDriverTime = [];
        self::$lastVehicleTime = [];
    }
}
