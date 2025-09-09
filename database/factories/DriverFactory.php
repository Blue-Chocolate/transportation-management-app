<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class DriverFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name'       => $this->faker->name,
            'license'    => strtoupper($this->faker->bothify('LIC-####')),
        ];
    }
}
