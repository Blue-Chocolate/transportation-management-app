<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Driver;

class DriverFactory extends Factory
{
    protected $model = \App\Models\Driver::class;

    public function definition(): array
    {
        return [
            'name'                  => $this->faker->name(),
            'phone'                 => $this->faker->phoneNumber(),
            'email'                 => $this->faker->unique()->safeEmail(),
            'password'              => bcrypt('12345'),
            'emergency_contact'     => $this->faker->phoneNumber(),
            'license'               => strtoupper($this->faker->bothify('LIC-####')),
            'license_expiration'    => $this->faker->dateTimeBetween('now', '+5 years'),
            'date_of_birth'         => $this->faker->dateTimeBetween('-60 years', '-21 years'),
            'address'               => $this->faker->address(),
            'hire_date'             => $this->faker->dateTimeBetween('-10 years', 'now'),
            'employment_status'     => $this->faker->randomElement(['active', 'inactive']),
            'route_assignments'     => [$this->faker->city(), $this->faker->city()],
            'performance_rating'    => $this->faker->randomFloat(2, 3, 5),
            'medical_certified'     => $this->faker->boolean(80),
            'background_check_date' => $this->faker->dateTimeBetween('-3 years', 'now'),
            'profile_photo'         => null,
            'notes'                 => $this->faker->sentence(),
            'insurance_info'        => 'Policy #' . $this->faker->bothify('??-####'),
            'training_certifications' => ['Defensive Driving', 'First Aid'],
'user_id' => User::factory(),        ];
    }

    public function forUser($userId): static
    {
        return $this->state(fn () => ['user_id' => $userId]);
    }
}