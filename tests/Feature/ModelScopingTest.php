<?php

use App\Models\Company;
use App\Models\Driver;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('scopes drivers to the company', function () {
    $company1 = Company::factory()->create();
    $company2 = Company::factory()->create();

    $driver1 = Driver::factory()->create(['company_id' => $company1->id]);
    $driver2 = Driver::factory()->create(['company_id' => $company2->id]);

    $scopedDrivers = Driver::forCompany($company1->id)->get();

    expect($scopedDrivers)->toHaveCount(1);
    expect($scopedDrivers->first()->id)->toBe($driver1->id);
});

it('scopes vehicles to the company', function () {
    $company1 = Company::factory()->create();
    $company2 = Company::factory()->create();

    $vehicle1 = Vehicle::factory()->create(['company_id' => $company1->id]);
    $vehicle2 = Vehicle::factory()->create(['company_id' => $company2->id]);

    $scopedVehicles = Vehicle::forCompany($company1->id)->get();

    expect($scopedVehicles)->toHaveCount(1);
    expect($scopedVehicles->first()->id)->toBe($vehicle1->id);
});