<?php

use App\Models\{User, Client, Driver, Vehicle, Trip};
use App\Services\{TripValidationService, TripCreationService};
use App\Enums\{TripStatus, EmploymentStatus};
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create a user for testing
    $this->user = User::factory()->create();
    
    // Create related models
    $this->client = Client::factory()->create(['user_id' => $this->user->id]);
    $this->driver = Driver::factory()->create([
        'user_id' => $this->user->id,
        'employment_status' => EmploymentStatus::ACTIVE,
    ]);
    $this->vehicle = Vehicle::factory()->create(['user_id' => $this->user->id]);
    
    // Assign vehicle to driver - this is crucial for validation to pass
    $this->driver->vehicles()->attach($this->vehicle->id);
    
    // Act as the created user
    $this->actingAs($this->user);
});

test('can create trip successfully with valid data', function () {
    // Arrange: Prepare valid trip data
    $validTripData = [
        'user_id' => $this->user->id,
        'client_id' => $this->client->id,
        'driver_id' => $this->driver->id,
        'vehicle_id' => $this->vehicle->id,
        'start_time' => now()->addHour()->format('Y-m-d H:i:s'),
        'end_time' => now()->addHours(2)->format('Y-m-d H:i:s'),
        'status' => TripStatus::PLANNED->value,
        'description' => 'Test trip description',
    ];
    
    // Act: Validate and create trip
    $validationService = app(TripValidationService::class);
    $creationService = app(TripCreationService::class);
    
    $validatedData = $validationService->validateAndEnrich($validTripData);
    $trip = $creationService->create($validatedData);
    
    // Assert: Verify trip was created correctly
    expect($trip)
        ->toBeInstanceOf(Trip::class)
        ->and($trip->user_id)->toBe($this->user->id)
        ->and($trip->client_id)->toBe($this->client->id)
        ->and($trip->driver_id)->toBe($this->driver->id)
        ->and($trip->vehicle_id)->toBe($this->vehicle->id)
        ->and($trip->status)->toBe(TripStatus::PLANNED->value);
    
    // Verify trip exists in database
    $this->assertDatabaseHas('trips', [
        'id' => $trip->id,
        'user_id' => $this->user->id,
        'client_id' => $this->client->id,
        'driver_id' => $this->driver->id,
        'vehicle_id' => $this->vehicle->id,
        'status' => TripStatus::PLANNED->value,
    ]);
});

test('throws validation exception for overlapping trips', function () {
    // Arrange: Create an existing trip first
    $startTime = now()->addHour();
    $endTime = now()->addHours(2);
    
    Trip::create([
        'user_id' => $this->user->id,
        'client_id' => $this->client->id,
        'driver_id' => $this->driver->id,
        'vehicle_id' => $this->vehicle->id,
        'start_time' => $startTime,
        'end_time' => $endTime,
        'status' => TripStatus::PLANNED->value,
        'description' => 'Existing trip',
    ]);
    
    // Prepare overlapping trip data (same driver and overlapping time)
    $overlappingTripData = [
        'user_id' => $this->user->id,
        'client_id' => $this->client->id,
        'driver_id' => $this->driver->id, // Same driver = conflict
        'vehicle_id' => $this->vehicle->id, // Same vehicle = conflict
        'start_time' => $startTime->copy()->addMinutes(30)->format('Y-m-d H:i:s'), // Overlaps
        'end_time' => $startTime->copy()->addHours(3)->format('Y-m-d H:i:s'),
        'status' => TripStatus::PLANNED->value,
    ];
    
    // Act & Assert: Expect validation to throw exception
    $validationService = app(TripValidationService::class);
    
    $exceptionThrown = false;
    $errors = [];
    
    try {
        $validationService->validateAndEnrich($overlappingTripData);
    } catch (ValidationException $e) {
        $exceptionThrown = true;
        $errors = $e->errors();
    }
    
    expect($exceptionThrown)->toBeTrue('ValidationException should be thrown for overlapping trips');
    
    // Should have either driver_id or vehicle_id error (or both)
    expect($errors)->toSatisfy(function ($errors) {
        return isset($errors['driver_id']) || isset($errors['vehicle_id']);
    }, 'Should have driver or vehicle overlap error');
    
    // Verify no new trip was created
    expect(Trip::count())->toBe(1, 'Should only have the original trip, no new trip created');
});

test('validation fails for past trip', function () {
    $invalidTripData = [
        'user_id' => $this->user->id,
        'client_id' => $this->client->id,
        'driver_id' => $this->driver->id,
        'vehicle_id' => $this->vehicle->id,
        'start_time' => now()->subHour()->format('Y-m-d H:i:s'), // Past time
        'end_time' => now()->format('Y-m-d H:i:s'),
        'status' => TripStatus::PLANNED->value,
    ];
    
    $validationService = app(TripValidationService::class);
    
    $exceptionThrown = false;
    $errors = [];
    
    try {
        $validationService->validateAndEnrich($invalidTripData);
    } catch (ValidationException $e) {
        $exceptionThrown = true;
        $errors = $e->errors();
    }
    
    expect($exceptionThrown)->toBeTrue('Should throw ValidationException for past trip');
    expect($errors)->toHaveKey('start_time');
});

test('validation fails for end time before start time', function () {
    $invalidTripData = [
        'user_id' => $this->user->id,
        'client_id' => $this->client->id,
        'driver_id' => $this->driver->id,
        'vehicle_id' => $this->vehicle->id,
        'start_time' => now()->addHours(2)->format('Y-m-d H:i:s'),
        'end_time' => now()->addHour()->format('Y-m-d H:i:s'), // Before start
        'status' => TripStatus::PLANNED->value,
    ];
    
    $validationService = app(TripValidationService::class);
    
    $exceptionThrown = false;
    $errors = [];
    
    try {
        $validationService->validateAndEnrich($invalidTripData);
    } catch (ValidationException $e) {
        $exceptionThrown = true;
        $errors = $e->errors();
    }
    
    expect($exceptionThrown)->toBeTrue('Should throw ValidationException for end before start');
    expect($errors)->toHaveKey('end_time');
});

test('validation fails for inactive driver', function () {
    // Create an inactive driver
    $inactiveDriver = Driver::factory()->create([
        'user_id' => $this->user->id,
        'employment_status' => EmploymentStatus::INACTIVE,
    ]);
    
    // Assign vehicle to inactive driver too
    $inactiveDriver->vehicles()->attach($this->vehicle->id);
    
    $invalidTripData = [
        'user_id' => $this->user->id,
        'client_id' => $this->client->id,
        'driver_id' => $inactiveDriver->id,
        'vehicle_id' => $this->vehicle->id,
        'start_time' => now()->addHour()->format('Y-m-d H:i:s'),
        'end_time' => now()->addHours(2)->format('Y-m-d H:i:s'),
        'status' => TripStatus::PLANNED->value,
    ];
    
    $validationService = app(TripValidationService::class);
    
    $exceptionThrown = false;
    $errors = [];
    
    try {
        $validationService->validateAndEnrich($invalidTripData);
    } catch (ValidationException $e) {
        $exceptionThrown = true;
        $errors = $e->errors();
    }
    
    expect($exceptionThrown)->toBeTrue('Should throw ValidationException for inactive driver');
    expect($errors)->toHaveKey('driver_id');
});

test('trip validation completes within acceptable time', function () {
    $validTripData = [
        'user_id' => $this->user->id,
        'client_id' => $this->client->id,
        'driver_id' => $this->driver->id,
        'vehicle_id' => $this->vehicle->id,
        'start_time' => now()->addHour()->format('Y-m-d H:i:s'),
        'end_time' => now()->addHours(2)->format('Y-m-d H:i:s'),
        'status' => TripStatus::PLANNED->value,
    ];
    
    $startTime = microtime(true);
    
    $validationService = app(TripValidationService::class);
    $validationService->validateAndEnrich($validTripData);
    
    $endTime = microtime(true);
    $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
    
    // Assert validation completes in under 1 second (1000ms)
    expect($executionTime)->toBeLessThan(1000, 'Validation should complete within 1 second');
});

test('trip data gets enriched with additional information', function () {
    $baseTripData = [
        'user_id' => $this->user->id,
        'client_id' => $this->client->id,
        'driver_id' => $this->driver->id,
        'vehicle_id' => $this->vehicle->id,
        'start_time' => now()->addHour()->format('Y-m-d H:i:s'),
        'end_time' => now()->addHours(2)->format('Y-m-d H:i:s'),
        'status' => TripStatus::PLANNED->value,
        // Note: No description provided - should be auto-generated
    ];
    
    $validationService = app(TripValidationService::class);
    $enrichedData = $validationService->validateAndEnrich($baseTripData);
    
    // Assert data was enriched with description
    expect($enrichedData)
        ->toHaveKey('description')
        ->and($enrichedData['description'])->not->toBeEmpty()
        ->and($enrichedData['description'])->toBeString();
});