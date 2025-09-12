<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Trip;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Client;
use App\Models\Company;
use App\Enums\TripStatus;

class TripFactory extends Factory
{
    protected $model = Trip::class;

    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('-1 month', '+1 month');
        $end = (clone $start)->modify('+' . rand(1, 8) . ' hours');

        return [
            'start_time' => $start,
            'end_time' => $end,
            'description' => $this->faker->sentence,
            'status' => $this->faker->randomElement(TripStatus::cases()),
            'company_id' => Company::factory(),
        ];
    }

    public function forDriverAndVehicle(Driver $driver, Vehicle $vehicle): self
    {
        return $this->state(function () use ($driver, $vehicle) {
            $start = $this->faker->dateTimeBetween('-1 month', '+1 month');
            $end = (clone $start)->modify('+' . rand(1, 8) . ' hours');

            $company_id = $driver->company_id ?? $vehicle->company_id ?? Company::factory()->create()->id;

            return [
                'driver_id' => $driver->id,
                'vehicle_id' => $vehicle->id,
                'company_id' => $company_id,
            ];
        });
    }

    public function forClient(Client $client): self
    {
        return $this->state(function () use ($client) {
            return [
                'client_id' => $client->id,
                'company_id' => $client->company_id,
            ];
        });
    }

    public function active(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => TripStatus::ACTIVE,
            'start_time' => now()->subHours(1),
            'end_time' => now()->addHours(rand(1, 4)),
        ]);
    }
}