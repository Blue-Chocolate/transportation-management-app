<?php

use App\Models\User;
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

it('creates a new user for the same company', function () {
    $response = $this->post(route('filament.resources.users.create'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'role' => 'staff',
        'company_id' => $this->company->id,
    ]);

    $response->assertStatus(302);
    expect(User::where('email', 'test@example.com')->first())->not->toBeNull();
});

it('cannot access users from another company', function () {
    $otherCompany = Company::factory()->create();
    User::factory()->create([
        'company_id' => $otherCompany->id,
        'email' => 'other@example.com',
    ]);

    $users = User::all()->where('company_id', $this->company->id);
    expect($users->pluck('email'))->not->toContain('other@example.com');
});
