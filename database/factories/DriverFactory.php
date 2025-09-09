<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class DriverFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id'            => Company::factory(),
            'name'                  => $this->faker->name,
            'phone'                 => $this->faker->phoneNumber,
            'email'                 => $this->faker->safeEmail,
            'emergency_contact'     => $this->faker->phoneNumber,

            'license'               => strtoupper($this->faker->bothify('LIC-####')),
            'license_expiration'    => $this->faker->dateTimeBetween('now', '+5 years'),
            'date_of_birth'         => $this->faker->dateTimeBetween('-60 years', '-21 years'),
            'address'               => $this->faker->address,
            'hire_date'             => $this->faker->dateTimeBetween('-10 years', 'now'),
            'employment_status'     => $this->faker->randomElement(['active', 'inactive']),

            'route_assignments'     => [$this->faker->city, $this->faker->city],
            'performance_rating'    => $this->faker->randomFloat(2, 3, 5),
            'medical_certified'     => $this->faker->boolean(80), // 80% certified
            'background_check_date' => $this->faker->dateTimeBetween('-3 years', 'now'),

            'profile_photo'         => null, // you can use fake image paths if needed
            'notes'                 => $this->faker->sentence,
            'insurance_info'        => 'Policy #' . $this->faker->bothify('??-####'),
            'training_certifications' => ['Defensive Driving', 'First Aid'],
        ];
    }
}
