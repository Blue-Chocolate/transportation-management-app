<?php
namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Client;
use App\Models\Trip;
use App\Enums\TripStatus;
use App\Filament\Admin\Resources\TripResource\Pages\CreateTrip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;

class CreateTripTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    #[Test]
    public function it_validates_end_time_after_start_time(): void
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);
        $driver = Driver::factory()->create(['user_id' => $this->user->id]);
        $vehicle = Vehicle::factory()->create(['user_id' => $this->user->id]);
        $driver->vehicles()->attach($vehicle);

        $invalidData = [
            'client_id' => $client->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'start_time' => now()->addHours(2)->format('Y-m-d H:i:s'),
            'end_time' => now()->addHour()->format('Y-m-d H:i:s'), // End before start!
            'status' => TripStatus::PLANNED->value,
            'description' => 'Test trip',
        ];

        $this->expectException(ValidationException::class);

        // Create instance of CreateTrip page
        $createTripPage = new CreateTrip();
        
        // Use reflection to call the protected method
        $reflection = new ReflectionClass($createTripPage);
        $method = $reflection->getMethod('mutateFormDataBeforeCreate');
        $method->setAccessible(true);
        $method->invoke($createTripPage, $invalidData);
    }

    #[Test]
    public function it_prevents_overlapping_trips(): void
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);
        $driver = Driver::factory()->create(['user_id' => $this->user->id]);
        $vehicle = Vehicle::factory()->create(['user_id' => $this->user->id]);
        $driver->vehicles()->attach($vehicle);

        // Create existing trip
        Trip::factory()->create([
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'start_time' => now()->addHours(1),
            'end_time' => now()->addHours(3),
            'vehicle_type' => 'car',
        ]);

        // Try to create overlapping trip
        $overlappingData = [
            'client_id' => $client->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'start_time' => now()->addHours(2)->format('Y-m-d H:i:s'), // Overlaps!
            'end_time' => now()->addHours(4)->format('Y-m-d H:i:s'),
            'status' => TripStatus::PLANNED->value,
            'description' => 'Overlapping trip',
        ];

        $this->expectException(ValidationException::class);

        // Create instance of CreateTrip page
        $createTripPage = new CreateTrip();
        
        // Use reflection to call the protected method
        $reflection = new ReflectionClass($createTripPage);
        $method = $reflection->getMethod('mutateFormDataBeforeCreate');
        $method->setAccessible(true);
        $method->invoke($createTripPage, $overlappingData);
    }
}
