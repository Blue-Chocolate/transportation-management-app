<?php

namespace Database\Factories;

use App\Models\{Company, Client, Driver, Vehicle};
use Illuminate\Database\Eloquent\Factories\Factory;

class TripFactory extends Factory
{
    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('-1 month', '+1 month');
        $end = (clone $start)->modify('+' . rand(1, 8) . ' hours');

        // Create a vehicle first so we can pull its type
        $vehicle = Vehicle::factory()->create();

        return [
            'company_id'   => Company::factory(),
            'client_id'    => Client::factory(),
            'driver_id'    => Driver::factory(),
            'vehicle_id'   => $vehicle->id,
            'vehicle_type' => $vehicle->vehicle_type ?? $this->faker->randomElement(['car', 'van', 'truck', 'Bus']),
            'start_time'   => $start,
            'end_time'     => $end,
            'description'  => $this->faker->sentence,
            'status'       => $this->faker->randomElement(['planned', 'active', 'completed', 'cancelled']),
        ];
    }

    // Ensure no overlaps for a driver/vehicle
    public function forDriverAndVehicle(Driver $driver, Vehicle $vehicle): self
    {
        return $this->state(function (array $attributes) use ($driver, $vehicle) {
            $start = $this->faker->dateTimeBetween('-1 month', '+1 month');
            $end = (clone $start)->modify('+' . rand(1, 8) . ' hours');

            while (
                \App\Models\Trip::where('driver_id', $driver->id)
                    ->where(function ($q) use ($start, $end) {
                        $q->whereBetween('start_time', [$start, $end])
                          ->orWhereBetween('end_time', [$start, $end])
                          ->orWhere(function ($q2) use ($start, $end) {
                              $q2->where('start_time', '<=', $start)
                                 ->where('end_time', '>=', $end);
                          });
                    })->exists() ||
                \App\Models\Trip::where('vehicle_id', $vehicle->id)
                    ->where(function ($q) use ($start, $end) {
                        $q->whereBetween('start_time', [$start, $end])
                          ->orWhereBetween('end_time', [$start, $end])
                          ->orWhere(function ($q2) use ($start, $end) {
                              $q2->where('start_time', '<=', $start)
                                 ->where('end_time', '>=', $end);
                          });
                    })->exists()
            ) {
                $start = $this->faker->dateTimeBetween('-1 month', '+1 month');
                $end = (clone $start)->modify('+' . rand(1, 8) . ' hours');
            }

            return [
                'company_id'   => $driver->company_id,
                'client_id'    => Client::factory()->create(['company_id' => $driver->company_id]),
                'driver_id'    => $driver->id,
                'vehicle_id'   => $vehicle->id,
                'vehicle_type' => $vehicle->vehicle_type, // ✅ fixed
                'start_time'   => $start,
                'end_time'     => $end,
            ];
        });
    }
}
