<?php

namespace Database\Seeders;

use App\Models\{Driver, Vehicle, Client, Trip};
use Illuminate\Database\Seeder;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1️⃣ Create vehicles
        $vehicles = Vehicle::factory()->count(15)->create();

        // 2️⃣ Create drivers
        $drivers = Driver::factory()->count(10)->create();

        // 3️⃣ Create clients
        $clients = Client::factory()->count(5)->create();

        // 4️⃣ Assign 1-3 vehicles to each driver and create trips
        foreach ($drivers as $driver) {
            $assignedVehicles = $vehicles->random(rand(1, 3));
            $driver->vehicles()->sync($assignedVehicles->pluck('id'));

            foreach (range(1, 5) as $i) {
                $vehicle = $assignedVehicles->random();
                $client  = $clients->random();

                Trip::factory()->forDriverAndVehicle($driver, $vehicle)->create([
                    'driver_id'    => $driver->id,
                    'vehicle_id'   => $vehicle->id,
                    'vehicle_type' => $vehicle->vehicle_type, // ✅ ضروري
                    'client_id'    => $client->id,
                ]);
            }
        }

        // 5️⃣ Create admin user
        User::factory()->create([
            'name'  => 'Admin User',
            'email' => 'admin@example.com',
            'role'  => 'admin',
        ]);
    }
}
