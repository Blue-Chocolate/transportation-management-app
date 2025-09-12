<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Company;

class VehicleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->word . ' ' . $this->faker->randomElement(['Car', 'Van', 'Truck', 'Bus']),
            'registration_number' => strtoupper($this->faker->unique()->bothify('??-####')),
            'vehicle_type' => $this->faker->randomElement(['car', 'van', 'truck', 'bus']),
            'company_id' => Company::factory(),
        ];
    }
}