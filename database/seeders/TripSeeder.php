<?php 


use App\Models\Trip;
use App\Models\Driver;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class TripSeeder extends Seeder
{
    public function run()
    {
        $drivers = Driver::all();
        $vehicles = Vehicle::all();

        foreach ($drivers as $index => $driver) {
            $vehicle = $vehicles->get($index % $vehicles->count());

            Trip::create([
                'driver_id' => $driver->id,
                'vehicle_id' => $vehicle->id,
                'start_time' => now()->addHours($index * 3), // spaced 3 hours apart
                'end_time' => now()->addHours($index * 3 + 2),
                'description' => 'Seeder trip for driver ' . $driver->name,
            ]);
        }
    }
}
