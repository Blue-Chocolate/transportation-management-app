<?php

use App\Models\Company;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Client;
use App\Models\Trip;
use App\Enums\TripStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('prevents creating trips with drivers from other companies', function () {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();
    $driver = Driver::factory()->create(['company_id' => $otherCompany->id]);
    $vehicle = Vehicle::factory()->create(['company_id' => $company->id]);
    $client = Client::factory()->create(['company_id' => $company->id]);

    $driver->vehicles()->attach($vehicle);

    $trip = Trip::factory()->make([
        'client_id' => $client->id,
        'driver_id' => $driver->id,
        'vehicle_id' => $vehicle->id,
        'start_time' => now(),
        'end_time' => now()->addHour(),
        'status' => TripStatus::ACTIVE,
        'company_id' => $company->id,
    ]);

    $trip->save();
})->throws(ValidationException::class, 'Driver must belong to the same company.');

it('prevents creating trips with vehicles from other companies', function () {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();
    $driver = Driver::factory()->create(['company_id' => $company->id]);
    $vehicle = Vehicle::factory()->create(['company_id' => $otherCompany->id]);
    $client = Client::factory()->create(['company_id' => $company->id]);

    $driver->vehicles()->attach($vehicle);

    $trip = Trip::factory()->make([
        'client_id' => $client->id,
        'driver_id' => $driver->id,
        'vehicle_id' => $vehicle->id,
        'start_time' => now(),
        'end_time' => now()->addHour(),
        'status' => TripStatus::ACTIVE,
        'company_id' => $company->id,
    ]);

    $trip->save();
})->throws(ValidationException::class, 'Vehicle must belong to the same company.');