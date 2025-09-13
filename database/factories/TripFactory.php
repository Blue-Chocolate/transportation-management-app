<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\{Client, Driver, Vehicle, User};

class TripFactory extends Factory
{
    protected $model = \App\Models\Trip::class;

    public function definition(): array
    {
        $startTime = $this->faker->dateTimeBetween('-1 month', '+1 month');
        $endTime = (clone $startTime)->modify('+' . $this->faker->numberBetween(1, 24) . ' hours');

        return [
            'client_id'     => null, // Optional, can be set later
            'driver_id'     => null, // To be injected
            'vehicle_id'    => null, // To be injected
            'user_id'       => null, // To be injected
            'vehicle_type'  => $this->faker->randomElement(['car', 'van', 'truck', 'bus']),
            'start_time'    => $startTime,
            'end_time'      => $endTime,
            'description'   => $this->faker->sentence(),
            'status'        => $this->faker->randomElement(['planned', 'active', 'completed', 'cancelled']),
        ];
    }

    public function forUser($userId): static
    {
        return $this->state(fn () => ['user_id' => $userId]);
    }

    public function withDriver($driverId): static
    {
        return $this->state(fn () => ['driver_id' => $driverId]);
    }

    public function withVehicle($vehicleId): static
    {
        return $this->state(fn () => ['vehicle_id' => $vehicleId]);
    }

    public function withClient($clientId): static
    {
        return $this->state(fn () => ['client_id' => $clientId]);
    }
}