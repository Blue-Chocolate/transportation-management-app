<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Driver;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Driver>
 */
class DriverFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Driver::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
        'name' => $this->faker->name(),
            'phone'                   => fake()->phoneNumber(),
            'email'                   => fake()->unique()->safeEmail(),
            'password'                => Hash::make('password'),
            'emergency_contact'       => fake()->phoneNumber(),
            'license'                 => strtoupper(fake()->bothify('LIC-####')),
            'license_expiration'      => fake()->dateTimeBetween('now', '+5 years'),
            'date_of_birth'           => fake()->dateTimeBetween('-60 years', '-21 years'),
            'address'                 => fake()->address(),
            'hire_date'               => fake()->dateTimeBetween('-2 years', 'now'),
            'employment_status'       => 'active',
            'route_assignments'       => [fake()->city(), fake()->city()],
            'performance_rating'      => fake()->randomFloat(2, 3.5, 5.0),
            'medical_certified'       => true,
            'background_check_date'   => fake()->dateTimeBetween('-1 year', 'now'),
            'profile_photo'           => null,
            'notes'                   => fake()->optional()->sentence(),
            'insurance_info'          => 'Policy #' . fake()->bothify('??-####'),
            'training_certifications' => ['Defensive Driving', 'First Aid'],
            'user_id'                 => User::factory(),
            'remember_token'          => null,
            'email_verified_at'       => now(),
        ];
    }

    /**
     * Indicate that the driver should be associated with a specific user.
     */
    public function forUser($userId): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $userId,
        ]);
    }

    /**
     * Indicate that the driver is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'employment_status' => 'inactive',
        ]);
    }

    /**
     * Indicate that the driver's email is unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}