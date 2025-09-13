<?php
namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Trip;
use App\Models\User;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Client;
use App\Enums\TripStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;

class TripTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Driver $driver;
    protected Vehicle $vehicle;
    protected Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->client = Client::factory()->create(['user_id' => $this->user->id]);
        $this->driver = Driver::factory()->create(['user_id' => $this->user->id]);
        $this->vehicle = Vehicle::factory()->create(['user_id' => $this->user->id]);
        
        // Assign vehicle to driver
        $this->driver->vehicles()->attach($this->vehicle);
    }

    #[Test]
    public function end_time_must_be_after_start_time(): void
    {
        $this->expectException(ValidationException::class);

        Trip::create([
            'client_id' => $this->client->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'user_id' => $this->user->id,
            'start_time' => now()->addHours(2),
            'end_time' => now()->addHour(), // End before start
            'status' => TripStatus::PLANNED->value,
            'description' => 'Test trip',
            'vehicle_type' => 'car',
        ]);
    }

    #[Test]
    public function driver_cannot_have_overlapping_trips(): void
    {
        // Create first trip
        Trip::factory()->create([
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'start_time' => now()->addHours(1),
            'end_time' => now()->addHours(3),
            'vehicle_type' => 'car',
        ]);

        $this->expectException(ValidationException::class);

        // Try to create overlapping trip with same driver
        Trip::create([
            'client_id' => $this->client->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'user_id' => $this->user->id,
            'start_time' => now()->addHours(2), // Overlaps
            'end_time' => now()->addHours(4),
            'status' => TripStatus::PLANNED->value,
            'description' => 'Overlapping trip',
            'vehicle_type' => 'car',
        ]);
    }

    #[Test]
    public function vehicle_cannot_have_overlapping_trips(): void
    {
        // Create another driver for the same user
        $anotherDriver = Driver::factory()->create(['user_id' => $this->user->id]);
        $anotherDriver->vehicles()->attach($this->vehicle);

        // Create first trip
        Trip::factory()->create([
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'start_time' => now()->addHours(1),
            'end_time' => now()->addHours(3),
            'vehicle_type' => 'truck',
        ]);

        $this->expectException(ValidationException::class);

        // Try to create overlapping trip with same vehicle but different driver
        Trip::create([
            'client_id' => $this->client->id,
            'driver_id' => $anotherDriver->id,
            'vehicle_id' => $this->vehicle->id, // Same vehicle
            'user_id' => $this->user->id,
            'start_time' => now()->addHours(2), // Overlaps
            'end_time' => now()->addHours(4),
            'status' => TripStatus::PLANNED->value,
            'description' => 'Vehicle overlap trip',
            'vehicle_type' => 'truck',
        ]);
    }
}

