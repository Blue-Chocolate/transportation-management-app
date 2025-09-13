<?php

use App\Models\Company;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Client;
use App\Models\Trip;
use App\Enums\TripStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('returns a Driver instance from trip driver relationship', function () {
    $company = Company::factory()->create();
    $driver = Driver::factory()->create(['company_id' => $company->id]);
    $vehicle = Vehicle::factory()->create(['company_id' => $company->id]);
    $client = Client::factory()->create(['company_id' => $company->id]);

    $driver->vehicles()->attach($vehicle);

    $trip = Trip::factory()->create([
        'client_id' => $client->id,
        'driver_id' => $driver->id,
        'vehicle_id' => $vehicle->id,
        'company_id' => $company->id,
        'start_time' => now(),
        'end_time' => now()->addHour(),
        'status' => TripStatus::ACTIVE,
    ]);

    expect($trip->driver)->toBeInstanceOf(Driver::class);
});

it('returns a Vehicle instance from trip vehicle relationship', function () {
    $company = Company::factory()->create();
    $driver = Driver::factory()->create(['company_id' => $company->id]);
    $vehicle = Vehicle::factory()->create(['company_id' => $company->id]);
    $client = Client::factory()->create(['company_id' => $company->id]);

    $driver->vehicles()->attach($vehicle);

    $trip = Trip::factory()->create([
        'client_id' => $client->id,
        'driver_id' => $driver->id,
        'vehicle_id' => $vehicle->id,
        'company_id' => $company->id,
        'start_time' => now(),
        'end_time' => now()->addHour(),
        'status' => TripStatus::ACTIVE,
    ]);

    expect($trip->vehicle)->toBeInstanceOf(Vehicle::class);
});

it('returns a Client instance from trip client relationship', function () {
    $company = Company::factory()->create();
    $driver = Driver::factory()->create(['company_id' => $company->id]);
    $vehicle = Vehicle::factory()->create(['company_id' => $company->id]);
    $client = Client::factory()->create(['company_id' => $company->id]);

    $driver->vehicles()->attach($vehicle);

    $trip = Trip::factory()->create([
        'client_id' => $client->id,
        'driver_id' => $driver->id,
        'vehicle_id' => $vehicle->id,
        'company_id' => $company->id,
        'start_time' => now(),
        'end_time' => now()->addHour(),
        'status' => TripStatus::ACTIVE,
    ]);

    expect($trip->client)->toBeInstanceOf(Client::class);
});

it('returns a Company instance from trip company relationship', function () {
    $company = Company::factory()->create();
    $driver = Driver::factory()->create(['company_id' => $company->id]);
    $vehicle = Vehicle::factory()->create(['company_id' => $company->id]);
    $client = Client::factory()->create(['company_id' => $company->id]);

    $driver->vehicles()->attach($vehicle);

    $trip = Trip::factory()->create([
        'client_id' => $client->id,
        'driver_id' => $driver->id,
        'vehicle_id' => $vehicle->id,
        'company_id' => $company->id,
        'start_time' => now(),
        'end_time' => now()->addHour(),
        'status' => TripStatus::ACTIVE,
    ]);

    expect($trip->company)->toBeInstanceOf(Company::class);
});