<?php

namespace Tests\Unit;

use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Trip;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TripTest extends TestCase
{
    use RefreshDatabase;

    public function test_end_time_must_be_after_start_time()
    {
        $driver = Driver::factory()->create();
        $vehicle = Vehicle::factory()->create();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('End time must be after start time.');

        Trip::create([
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'start_time' => Carbon::now(),
            'end_time' => Carbon::now()->subHour(), // End before start
            'description' => 'Test trip',
        ]);
    }

    public function test_driver_cannot_have_overlapping_trips()
    {
        $driver = Driver::factory()->create();
        $vehicle1 = Vehicle::factory()->create();
        $vehicle2 = Vehicle::factory()->create();

        // Create first trip
        Trip::create([
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle1->id,
            'start_time' => Carbon::now(),
            'end_time' => Carbon::now()->addHours(2),
            'description' => 'First trip',
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Driver has an overlapping trip.');

        // Overlapping second trip for same driver
        Trip::create([
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle2->id,
            'start_time' => Carbon::now()->addHour(),
            'end_time' => Carbon::now()->addHours(3),
            'description' => 'Overlapping trip',
        ]);
    }

    public function test_vehicle_cannot_have_overlapping_trips()
    {
        $driver1 = Driver::factory()->create();
        $driver2 = Driver::factory()->create();
        $vehicle = Vehicle::factory()->create();

        // Create first trip
        Trip::create([
            'driver_id' => $driver1->id,
            'vehicle_id' => $vehicle->id,
            'start_time' => Carbon::now(),
            'end_time' => Carbon::now()->addHours(2),
            'description' => 'First trip',
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Vehicle has an overlapping trip.');

        // Overlapping second trip for same vehicle
        Trip::create([
            'driver_id' => $driver2->id,
            'vehicle_id' => $vehicle->id,
            'start_time' => Carbon::now()->addHour(),
            'end_time' => Carbon::now()->addHours(3),
            'description' => 'Overlapping trip',
        ]);
    }
}