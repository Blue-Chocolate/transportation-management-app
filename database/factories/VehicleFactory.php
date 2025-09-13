<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleFactory extends Factory
{
    protected $model = \App\Models\Vehicle::class;

    public function definition(): array
    {
        return [
            'name'                => $this->faker->word() . ' ' . $this->faker->randomElement(['Car', 'Van', 'Truck', 'Bus']),
            'registration_number' => strtoupper($this->faker->bothify('??-####')),
            'vehicle_type'        => $this->faker->randomElement(['car', 'van', 'truck', 'bus']),
            'user_id'             => null, // to be injected
        ];
    }

    public function forUser($userId): static
    {
        return $this->state(fn () => ['user_id' => $userId]);
    }
}