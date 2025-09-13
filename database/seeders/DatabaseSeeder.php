<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{Driver, Vehicle, Client, Trip, User};

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create an admin user
        $admin = \App\Models\User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        // Create 3 additional regular users
        $users = User::factory()->count(5)->create([
            'role' => 'user',
        ]);
        $users->prepend($admin); // Include admin in the users collection for seeding

        // Seed data for each user
        foreach ($users as $user) {
            // Create 5 clients for the user
            Client::factory(5)->forUser($user->id)->create();

            // Create 5 drivers for the user
            $drivers = Driver::factory(5)->forUser($user->id)->create();

            // Create 5 vehicles for the user
            $vehicles = Vehicle::factory(5)->forUser($user->id)->create();

            // Assign drivers to vehicles (pivot table) with user_id
            foreach ($drivers as $driver) {
                $selectedVehicles = $vehicles->random(rand(1, 3));
                foreach ($selectedVehicles as $vehicle) {
                    \DB::table('driver_vehicle')->insert([
                        'driver_id' => $driver->id,
                        'vehicle_id' => $vehicle->id,
                        'user_id' => $user->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Create 10 trips for the user, assigning random drivers, vehicles, and optional clients
            $clients = Client::where('user_id', $user->id)->get();
            foreach (range(1, 10) as $i) {
                Trip::factory()
                    ->forUser($user->id)
                    ->withDriver($drivers->random()->id)
                    ->withVehicle($vehicles->random()->id)
                    ->withClient($clients->isNotEmpty() ? $clients->random()->id : null)
                    ->create();
            }
        }
    }
}