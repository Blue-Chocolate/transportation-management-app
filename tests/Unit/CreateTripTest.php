<?php

namespace Tests\Unit;

use App\Models\Client;
use App\Models\Driver;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class CreateTripTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_prevents_overlapping_trips()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $client = Client::factory()->create(['user_id' => $user->id]);
        $driver = Driver::factory()->create(['user_id' => $user->id]);
        $vehicle = Vehicle::factory()->create(['user_id' => $user->id]);

        // Assign vehicle to driver with user_id
        $driver->vehicles()->attach($vehicle->id, ['user_id' => $user->id]);

        // Create an existing trip
        $existingTrip = Trip::create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'vehicle_type' => $vehicle->vehicle_type,
            'start_time' => Carbon::now()->addHour(),
            'end_time' => Carbon::now()->addHours(2),
            'status' => \App\Enums\TripStatus::PLANNED,
        ]);

        // Try to create a new overlapping trip
        $newTripData = [
            'user_id' => $user->id,
            'client_id' => $client->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'vehicle_type' => $vehicle->vehicle_type,
            'start_time' => Carbon::now()->addHour()->addMinutes(30),
            'end_time' => Carbon::now()->addHours(3),
            'status' => \App\Enums\TripStatus::PLANNED,
        ];

        $this->expectException(ValidationException::class);

        app(\App\Filament\Admin\Resources\TripResource\Pages\CreateTrip::class)
            ->mutateFormDataBeforeCreate($newTripData);
    }

    /** @test */
    public function it_validates_end_time_after_start_time()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $client = Client::factory()->create(['user_id' => $user->id]);
        $driver = Driver::factory()->create(['user_id' => $user->id]);
        $vehicle = Vehicle::factory()->create(['user_id' => $user->id]);

        // Assign vehicle to driver with user_id
        $driver->vehicles()->attach($vehicle->id, ['user_id' => $user->id]);

        $invalidTripData = [
            'user_id' => $user->id,
            'client_id' => $client->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'vehicle_type' => $vehicle->vehicle_type,
            'start_time' => Carbon::now()->addHour(),
            'end_time' => Carbon::now(), // End before start
            'status' => \App\Enums\TripStatus::PLANNED,
        ];

        $this->expectException(ValidationException::class);

        app(\App\Filament\Admin\Resources\TripResource\Pages\CreateTrip::class)
            ->mutateFormDataBeforeCreate($invalidTripData);
    }
}
