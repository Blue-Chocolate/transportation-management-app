<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Company;

class VehicleFactory extends Factory
{
    protected $model = \App\Models\Vehicle::class;

    public function __construct($count = null, ? \Illuminate\Support\Collection $states = null, ? \Illuminate\Support\Collection $has = null, ? \Illuminate\Support\Collection $for = null, ? \Illuminate\Support\Collection $afterMaking = null, ? \Illuminate\Support\Collection $afterCreating = null, $connection = null, ? \Illuminate\Support\Collection $recycle = null)
    {
        parent::__construct($count, $states, $has, $for, $afterMaking, $afterCreating, $connection, $recycle);

        $this->faker->addProvider(new \Faker\Provider\en_US\Company($this->faker));
    }

    public function definition(): array
    {
        return [
            'name' => $this->faker->word . ' ' . $this->faker->randomElement(['Car', 'Van', 'Truck', 'Bus']),
            'registration_number' => strtoupper($this->faker->unique()->bothify('??-####')),
            'vehicle_type' => $this->faker->randomElement(['car', 'van', 'truck', 'bus']),
            'company_id' => Company::factory(),
        ];
    }

    public function forCompany($companyId): self
    {
        return $this->state(function () use ($companyId) {
            return [
                'company_id' => $companyId,
            ];
        });
    }
}