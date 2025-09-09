<?php 

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name'       => $this->faker->name,
            'email'      => $this->faker->safeEmail,
            'phone'      => $this->faker->phoneNumber,
        ];
    }
}
