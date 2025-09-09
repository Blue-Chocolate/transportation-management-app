<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{Company, Client, Driver, Vehicle, Trip};

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create 5 companies with related data
        Company::factory()
            ->count(5)
            ->has(Client::factory()->count(10))
            ->has(Driver::factory()->count(10)) // now includes extended fields
            ->has(Vehicle::factory()->count(10))
            ->create()
            ->each(function ($company) {
                // Attach trips per company
                Trip::factory()->count(20)->create([
                    'company_id' => $company->id,
                    'client_id'  => $company->clients()->inRandomOrder()->first()->id ?? null,
                    'driver_id'  => $company->drivers()->inRandomOrder()->first()->id,
                    'vehicle_id' => $company->vehicles()->inRandomOrder()->first()->id,
                ]);
            });
    }
}
