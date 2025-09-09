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
            'name'                => $this->faker->word . ' ' . $this->faker->randomElement(['Truck','Van','Car']),
            'registration_number' => strtoupper($this->faker->bothify('??-####')),
        ];
    }
}
