<?php

namespace Database\Factories;

use App\Models\Trip;
use Illuminate\Database\Eloquent\Factories\Factory;

class TripFactory extends Factory
{
    protected $model = Trip::class;

    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('-1 month', '+1 month');
        $end   = (clone $start)->modify('+'.rand(1,8).' hours');

        return [
            'start_time'   => $start,
            'end_time'     => $end,
            'description'  => $this->faker->sentence,
            'status'       => $this->faker->randomElement(['planned', 'active', 'completed', 'cancelled']),
        ];
    }

    public function forDriverAndVehicle($driver, $vehicle): self
    {
        return $this->state(function () use ($driver, $vehicle) {
            $start = $this->faker->dateTimeBetween('-1 month', '+1 month');
            $end   = (clone $start)->modify('+'.rand(1,8).' hours');

            return [
                'driver_id'    => $driver->id,
                'vehicle_id'   => $vehicle->id,
                'vehicle_type' => $vehicle->vehicle_type, // ✅ مهم جدًا
            ];
        });
    }

    public function forClient($client): self
    {
        return $this->state(function () use ($client) {
            return [
                'client_id' => $client->id,
            ];
        });
    }
}
