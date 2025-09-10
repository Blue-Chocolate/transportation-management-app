<?php

namespace Database\Seeders;

use App\Models\{Company, Driver, Vehicle, Client, Trip};
use Illuminate\Database\Seeder;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Define vehicle types per company
        $vehicleTypes = [
            ['car', 'bus'], // Company 1: Cars and Buses
            ['bus'],        // Company 2: Buses only
            ['van'],        // Company 3: Vans only
            ['truck'],      // Company 4: Trucks only
            ['car', 'van'], // Company 5: Cars and Vans
        ];

        // Create 5 companies
        $companies = Company::factory()->count(5)->create();

        foreach ($companies as $index => $company) {
            // Create 10 drivers per company
            $drivers = Driver::factory()->count(10)->create([
                'company_id' => $company->id,
            ]);

            // Create vehicles with specific types for this company
            $vehicleTypeSet = $vehicleTypes[$index];
            $vehicles = Vehicle::factory()
                ->count(15)
                ->state(function (array $attributes) use ($vehicleTypeSet) {
                    return [
                        'vehicle_type' => fake()->randomElement($vehicleTypeSet),
                    ];
                })
                ->create([
                    'company_id' => $company->id,
                ]);

            // Assign 1-3 vehicles to each driver
            foreach ($drivers as $driver) {
                $assignedVehicles = $vehicles->random(rand(1, 3));
                $driver->vehicles()->sync($assignedVehicles->pluck('id'));

                // Create 5 trips per driver, ensuring no overlaps
                foreach (range(1, 5) as $i) {
                    Trip::factory()->forDriverAndVehicle($driver, $assignedVehicles->random())->create([
                        'company_id' => $company->id,
                    ]);
                }
            }

            // Create 5 clients per company
            Client::factory()->count(5)->create([
                'company_id' => $company->id,
            ]);
        }

        // Create 1 admin user
        User::factory()->create([
            'name'  => 'Admin User',
            'email' => 'admin@example.com',
            'role'  => 'admin',
        ]);
    }
}