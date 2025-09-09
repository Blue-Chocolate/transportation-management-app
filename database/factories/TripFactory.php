<?php


namespace Database\Factories;

use App\Models\{Company, Client, Driver, Vehicle};
use Illuminate\Database\Eloquent\Factories\Factory;

class TripFactory extends Factory
{
    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('-1 month', '+1 month');
        $end   = (clone $start)->modify('+'.rand(1,8).' hours');

        return [
            'company_id' => Company::factory(),
            'client_id'  => Client::factory(),
            'driver_id'  => Driver::factory(),
            'vehicle_id' => Vehicle::factory(),
            'start_time' => $start,
            'end_time'   => $end,
            'description'=> $this->faker->sentence,
            'status'     => $this->faker->randomElement(['planned','active','completed','cancelled']),
        ];
    }
}
