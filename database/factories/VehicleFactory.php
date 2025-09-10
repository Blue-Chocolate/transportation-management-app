<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id'          => Company::factory(),
            'name'                => $this->faker->word . ' ' . $this->faker->randomElement(['Car', 'Van', 'Truck', 'Bus']),
            'registration_number' => strtoupper($this->faker->bothify('??-####')),
            'vehicle_type'        => $this->faker->randomElement(['car', 'van', 'truck', 'bus']), // Match Trip enum
        ];
    }
}