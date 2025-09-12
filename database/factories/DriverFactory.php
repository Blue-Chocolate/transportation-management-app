<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use App\Models\Company;
use App\Enums\EmploymentStatus;

class DriverFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'phone' => $this->faker->phoneNumber,
            'email' => $this->faker->unique()->safeEmail,
            'password' => static::$password ??= Hash::make('password123'),
            'emergency_contact' => $this->faker->phoneNumber,
            'company_id' => Company::factory(),

            'license' => strtoupper($this->faker->unique()->bothify('LIC-####')),
            'license_expiration' => $this->faker->dateTimeBetween('now', '+5 years'),
            'date_of_birth' => $this->faker->dateTimeBetween('-60 years', '-21 years'),
            'address' => $this->faker->address,
            'hire_date' => $this->faker->dateTimeBetween('-10 years', 'now'),
            'employment_status' => $this->faker->randomElement(EmploymentStatus::cases()),

            'route_assignments' => [$this->faker->city, $this->faker->city],
            'performance_rating' => $this->faker->randomFloat(2, 3, 5),
            'medical_certified' => $this->faker->boolean(80),
            'background_check_date' => $this->faker->dateTimeBetween('-3 years', 'now'),

            'profile_photo' => null,
            'notes' => $this->faker->sentence,
            'insurance_info' => 'Policy #' . $this->faker->bothify('??-####'),
            'training_certifications' => ['Defensive Driving', 'First Aid'],
        ];
    }

    public function active(): self
    {
        return $this->state(fn (array $attributes) => [
            'employment_status' => EmploymentStatus::ACTIVE,
            'background_check_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'medical_certified' => true,
        ]);
    }
}