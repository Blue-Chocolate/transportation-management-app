<?php 

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class ClientFactory extends Factory
{     protected static ?string $password;

    public function definition(): array
    {
        return [
            'name'       => $this->faker->name,
            'email'      => $this->faker->safeEmail,
            'phone'      => $this->faker->phoneNumber,
            'password'   => bcrypt('12345')
        ];
    }
}
