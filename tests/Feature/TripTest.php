<?php

use App\Models\User;
use App\Models\Trip;
use App\Models\Client;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Company;
use App\Enums\TripStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->admin = User::factory()->create([
        'company_id' => $this->company->id,
        'role' => 'admin',
    ]);
    $this->client = Client::factory()->create(['company_id' => $this->company->id]);
    $this->driver = Driver::factory()->create(['company_id' => $this->company->id]);
    $this->vehicle = Vehicle::factory()->create(['company_id' => $this->company->id]);
    $this->actingAs($this->admin);
});

it('creates a trip for the company', function () {
    $trip = Trip::factory()->create([
        'company_id' => $this->company->id,
        'client_id' => $this->client->id,
        'driver_id' => $this->driver->id,
        'vehicle_id' => $this->vehicle->id,
        'status' => TripStatus::PLANNED,
    ]);

    expect(Trip::where('id', $trip->id)->first())->not->toBeNull();
});

it('does not allow trips from other companies', function () {
    $otherCompany = Company::factory()->create();
    Trip::factory()->create([
        'company_id' => $otherCompany->id,
        'client_id' => $this->client->id,
        'driver_id' => $this->driver->id,
        'vehicle_id' => $this->vehicle->id,
        'status' => TripStatus::PLANNED,
    ]);

    $trips = Trip::all()->where('company_id', $this->company->id);
    expect($trips)->toHaveCount(0);
});
