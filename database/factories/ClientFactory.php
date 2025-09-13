<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class ClientFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name'       => $this->faker->name(),
            'email'      => $this->faker->unique()->safeEmail(), // Added unique() to prevent duplicate email errors
            'phone'      => $this->faker->phoneNumber(),
            'password'   => bcrypt('12345'),
            'user_id'    => null, // Added to match schema
        ];
    }

    public function forUser($userId): static
    {
        return $this->state(fn () => ['user_id' => $userId]);
    }
}