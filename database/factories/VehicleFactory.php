<?php
namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
class VehicleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true) . ' ' . $this->faker->randomElement(['Car', 'Van', 'Truck']),
            'registration_number' => strtoupper($this->faker->lexify('??-????')),
            'vehicle_type' => $this->faker->randomElement(['car', 'van', 'truck', 'bus']),
            'user_id' => User::factory(), // This was missing!
        ];
    }
}