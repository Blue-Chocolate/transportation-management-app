<?php

namespace Tests\Feature\Resources;

use App\Enums\TripStatus;
use App\Filament\Admin\Resources\AvailabilityCheckerResource\Pages\ListAvailability;
use App\Models\{Driver, Trip, User, Vehicle};
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Livewire\livewire; // Pest Livewire plugin
use Tests\TestCase;

class AvailabilityCheckerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_available_drivers_filters_by_time_and_name_for_user(): void
    {
        $driver1 = Driver::factory()->forUser($this->user->id)->create(['name' => 'Available Driver']);
        $driver2 = Driver::factory()->forUser($this->user->id)->create(['name' => 'Busy Driver']);

        $start = Carbon::parse('2025-09-15 18:00:00');
        $end = Carbon::parse('2025-09-15 19:00:00');

        // Chain both withDriver and withVehicle to avoid null constraints
        Trip::factory()
            ->forUser($this->user->id)
            ->withDriver($driver2->id)
            ->withVehicle(Vehicle::factory()->forUser($this->user->id)->create()->id) // Create a vehicle for this trip
            ->create([
                'start_time' => $start->clone()->subMinutes(30),
                'end_time' => $end->clone()->addMinutes(30),
                'status' => TripStatus::ACTIVE,
            ]);

        $this->actingAs($this->user);

        $page = new ListAvailability();
        $page->name = 'Available Driver';
        $page->start_time = $start->toDateTimeString();
        $page->end_time = $end->toDateTimeString();

        $availableDrivers = $page->getAvailableDrivers();

        $this->assertCount(1, $availableDrivers);
        $this->assertEquals('Available Driver', $availableDrivers->first()->name);
    }

    public function test_available_vehicles_filters_by_time_and_name_for_user(): void
    {
        $vehicle1 = Vehicle::factory()->forUser($this->user->id)->create(['name' => 'Available Vehicle']);
        $vehicle2 = Vehicle::factory()->forUser($this->user->id)->create(['name' => 'Busy Vehicle']);

        $start = Carbon::parse('2025-09-15 18:00:00');
        $end = Carbon::parse('2025-09-15 19:00:00');

        // Chain both withDriver and withVehicle to avoid null constraints
        Trip::factory()
            ->forUser($this->user->id)
            ->withDriver(Driver::factory()->forUser($this->user->id)->create()->id) // Create a driver for this trip
            ->withVehicle($vehicle2->id)
            ->create([
                'start_time' => $start->clone()->subMinutes(30),
                'end_time' => $end->clone()->addMinutes(30),
                'status' => TripStatus::ACTIVE,
            ]);

        $this->actingAs($this->user);

        $page = new ListAvailability();
        $page->name = 'Available Vehicle';
        $page->start_time = $start->toDateTimeString();
        $page->end_time = $end->toDateTimeString();

        $availableVehicles = $page->getAvailableVehicles();

        $this->assertCount(1, $availableVehicles);
        $this->assertEquals('Available Vehicle', $availableVehicles->first()->name);
    }

    public function test_no_available_if_no_time_range(): void
    {
        $this->actingAs($this->user);

        $page = new ListAvailability();
        $page->start_time = null;
        $page->end_time = null;

        $availableDrivers = $page->getAvailableDrivers();
        $availableVehicles = $page->getAvailableVehicles();

        $this->assertCount(0, $availableDrivers);
        $this->assertCount(0, $availableVehicles);
    }
}