<?php

use App\Models\User;
use App\Models\Vehicle;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->admin = User::factory()->create([
        'company_id' => $this->company->id,
        'role' => 'admin',
    ]);
    $this->actingAs($this->admin);
});

it('creates a vehicle for the company', function () {
    $vehicle = Vehicle::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Truck 1',
    ]);

    expect(Vehicle::where('name', 'Truck 1')->first())->not->toBeNull();
});

it('does not show vehicles from other companies', function () {
    $otherCompany = Company::factory()->create();
    Vehicle::factory()->create([
        'company_id' => $otherCompany->id,
        'name' => 'Other Truck',
    ]);

    $vehicles = Vehicle::all()->where('company_id', $this->company->id);
    expect($vehicles->pluck('name'))->not->toContain('Other Truck');
});
