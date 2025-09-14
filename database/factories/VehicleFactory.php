<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleFactory extends Factory
{
    public function definition(): array
    {
        $type = $this->faker->randomElement(['car', 'van', 'truck', 'bus']);
        
        return [
            'name'                => $this->generateVehicleName($type),
            'registration_number' => strtoupper($this->faker->bothify('??-####')),
            'vehicle_type'        => $type,
            'user_id'             => User::factory(),
        ];
    }

    public function forUser($userId): static
    {
        return $this->state(fn () => ['user_id' => $userId]);
    }

    public function car(): static
    {
        return $this->state(fn () => [
            'vehicle_type' => 'car',
            'name' => $this->generateVehicleName('car')
        ]);
    }

    public function van(): static
    {
        return $this->state(fn () => [
            'vehicle_type' => 'van',
            'name' => $this->generateVehicleName('van')
        ]);
    }

    public function truck(): static
    {
        return $this->state(fn () => [
            'vehicle_type' => 'truck',
            'name' => $this->generateVehicleName('truck')
        ]);
    }

    public function bus(): static
    {
        return $this->state(fn () => [
            'vehicle_type' => 'bus',
            'name' => $this->generateVehicleName('bus')
        ]);
    }

    private function generateVehicleName(string $type): string
    {
        $brands = [
            'car' => ['Toyota Camry', 'Honda Civic', 'Ford Focus', 'Nissan Sentra'],
            'van' => ['Ford Transit', 'Mercedes Sprinter', 'Chevrolet Express', 'Ram ProMaster'],
            'truck' => ['Ford F-150', 'Chevrolet Silverado', 'Ram 1500', 'Toyota Tacoma'],
            'bus' => ['School Bus', 'City Bus', 'Charter Bus', 'Mini Bus']
        ];

        return $this->faker->randomElement($brands[$type]);
    }
}