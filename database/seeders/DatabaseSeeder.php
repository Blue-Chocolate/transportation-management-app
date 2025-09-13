<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{Company, Driver, Vehicle, Client, Trip, User};

class DatabaseSeeder extends Seeder

{


    
    public function run(): void
    {
        $companies = Company::factory()->count(3)->create();

        foreach ($companies as $company) {
            User::factory()->admin()->create([
                'name' => 'Admin ' . $company->name,
                'email' => 'admin@' . str_replace(' ', '', strtolower($company->name)) . '.com',
                'company_id' => $company->id,
            ]);

            $clients = Client::factory()->count(5)->create([
                'company_id' => $company->id,
            ]);

            $vehicles = Vehicle::factory()->count(10)->create([
                'company_id' => $company->id,
            ]);

            $drivers = Driver::factory()->active()->count(8)->create([
                'company_id' => $company->id,
            ]);

            foreach ($drivers as $driver) {
                $assignedVehicles = $vehicles->random(rand(1, 3));
                $driver->vehicles()->sync($assignedVehicles->pluck('id'));

                $attempts = 0;
                $maxAttempts = 5;
                foreach (range(1, 5) as $i) {
                    $vehicle = $assignedVehicles->random();
                    $client = $clients->random();

                    try {
                        Trip::factory()
                            ->forDriverAndVehicle($driver, $vehicle)
                            ->forClient($client)
                            ->create([
                                'company_id' => $company->id,
                            ]);
                    } catch (\Illuminate\Validation\ValidationException $e) {
                        $attempts++;
                        if ($attempts >= $maxAttempts) {
                            break;
                        }
                        $i--;
                        continue;
                    }
                }
            }

            $driver = $drivers->random();
            $vehicle = $driver->vehicles()->inRandomOrder()->first();
            if ($vehicle) {
                Trip::factory()
                    ->active()
                    ->forDriverAndVehicle($driver, $vehicle)
                    ->forClient($clients->random())
                    ->create([
                        'company_id' => $company->id,
                    ]);
            }
        }
        
    }
    
}