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
    $this->actingAs($this->admin, 'web'); // Specify the 'web' guard
});

it('creates a vehicle for the company', function () {
    $vehicle = Vehicle::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Truck 1',
        'registration_number' => 'TRUCK123', // Ensure unique registration number
    ]);

    expect(Vehicle::where('company_id', $this->company->id)
        ->where('name', 'Truck 1')
        ->first())->not->toBeNull();
});

it('does not show vehicles from other companies', function () {
    $otherCompany = Company::factory()->create();
    Vehicle::factory()->create([
        'company_id' => $otherCompany->id,
        'name' => 'Other Truck',
        'registration_number' => 'OTHER123', // Ensure unique registration number
    ]);

    $vehicles = Vehicle::where('company_id', $this->company->id)->get();
    expect($vehicles->pluck('name'))->not->toContain('Other Truck');
});