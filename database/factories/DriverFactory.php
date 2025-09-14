<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

class DriverFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'                    => $this->faker->name(),
            'phone'                   => $this->faker->phoneNumber(),
            'email'                   => $this->faker->unique()->safeEmail(),
            'password'                => bcrypt('password'),
            'emergency_contact'       => $this->faker->phoneNumber(),
            'license'                 => strtoupper($this->faker->bothify('LIC-####')),
            'license_expiration'      => $this->faker->dateTimeBetween('now', '+5 years'),
            'date_of_birth'           => $this->faker->dateTimeBetween('-60 years', '-21 years'),
            'address'                 => $this->faker->address(),
            'hire_date'               => $this->faker->dateTimeBetween('-2 years', 'now'),
            'employment_status'       => 'active', // Default to active for testing
            'route_assignments'       => json_encode([$this->faker->city(), $this->faker->city()]),
            'performance_rating'      => $this->faker->randomFloat(2, 3.5, 5.0),
            'medical_certified'       => true,
            'background_check_date'   => $this->faker->dateTimeBetween('-1 year', 'now'),
            'profile_photo'           => null,
            'notes'                   => $this->faker->optional()->sentence(),
            'insurance_info'          => 'Policy #' . $this->faker->bothify('??-####'),
            'training_certifications' => json_encode(['Defensive Driving', 'First Aid']),
            'user_id'                 => User::factory(),
        ];
    }

    public function forUser($userId): static
    {
        return $this->state(fn () => ['user_id' => $userId]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['employment_status' => 'inactive']);
    }
}