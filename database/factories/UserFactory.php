<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Company;

class UserFactory extends Factory
{
    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('123'),
            'remember_token' => Str::random(10),
            'role' => $this->faker->randomElement(['admin', 'superadmin']),
            'company_id' => Company::factory(),
        ];
    }

    public function unverified(): self
    {
        return $this->state(fn () => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): self
    {
        return $this->state(fn () => [
            'role' => 'admin',
        ]);
    }

    public function superadmin(): self
    {
        return $this->state(fn () => [
            'role' => 'superadmin',
            'company_id' => null, // Superadmins may not belong to a company
        ]);
    }
}
