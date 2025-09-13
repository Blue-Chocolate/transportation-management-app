<?php

namespace App\Filament\Admin\Resources\TripResource\Pages;

use App\Filament\Admin\Resources\TripResource;
use App\Models\Driver;
use App\Models\Trip;
use App\Models\Client;
use App\Models\Vehicle;
use App\Enums\TripStatus;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

class CreateTrip extends CreateRecord
{
    protected static string $resource = TripResource::class;

    /**
     * Initialize the create page
     */
    public function mount(): void
    {
        Log::info('CreateTrip: Form mounted');
        parent::mount();
        
        // Set default values if needed
        $this->form->fill([
            'start_time' => now()->addHour()->format('Y-m-d H:i:s'),
            'end_time' => now()->addHours(2)->format('Y-m-d H:i:s'),
            'status' => TripStatus::PLANNED->value,
        ]);
    }

    /**
     * Check if form should be displayed
     */
    public function hasForm(): bool
    {
        $hasForm = parent::hasForm();
        Log::info('CreateTrip: hasForm checked', ['has_form' => $hasForm]);
        return $hasForm;
    }

    /**
     * Get current form state for debugging
     */
    public function getFormState(): array
    {
        $state = parent::getFormState();
        Log::info('CreateTrip: Form state retrieved', ['state_keys' => array_keys($state)]);
        return $state;
    }

    /**
     * This method is called by Filament before creating the record
     * All validation and data mutation happens here
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        Log::info('mutateFormDataBeforeCreate: Processing data', ['data' => $data]);

        // 1️⃣ Set the authenticated user ID
        $data['user_id'] = Auth::id();

        if (!$data['user_id']) {
            throw ValidationException::withMessages([
                'user_id' => 'User must be authenticated to create a trip.',
            ]);
        }

        // 2️⃣ Parse and validate dates
        try {
            $start = Carbon::parse($data['start_time']);
            $end = Carbon::parse($data['end_time']);
        } catch (\Exception $e) {
            throw ValidationException::withMessages([
                'start_time' => 'Invalid date format.',
            ]);
        }

        // 3️⃣ Validate end time is after start time
        if ($end->lte($start)) {
            throw ValidationException::withMessages([
                'end_time' => 'End time must be after start time.',
            ]);
        }

        // 4️⃣ Validate start time is not in the past (optional business rule)
        if ($start->lt(now())) {
            throw ValidationException::withMessages([
                'start_time' => 'Trip cannot be scheduled in the past.',
            ]);
        }

        // 5️⃣ Validate that resources exist and belong to the user
        $this->validateResourceOwnership($data);

        // 6️⃣ Get the models for further validation
        $client = Client::where('user_id', $data['user_id'])->find($data['client_id']);
        $driver = Driver::where('user_id', $data['user_id'])->find($data['driver_id']);
        $vehicle = Vehicle::where('user_id', $data['user_id'])->find($data['vehicle_id']);

        // 7️⃣ Check if driver is assigned to the vehicle
        if (!$driver->vehicles()->where('vehicles.id', $vehicle->id)->exists()) {
            throw ValidationException::withMessages([
                'vehicle_id' => 'The selected vehicle is not assigned to the selected driver.',
            ]);
        }

        // 8️⃣ Auto-populate vehicle type from selected vehicle
        $data['vehicle_type'] = $vehicle->vehicle_type;

        // 9️⃣ Check for overlapping trips
        $this->checkForOverlappingTrips($data, $start, $end);

        // 🔟 Validate status
        $this->validateTripStatus($data);

        // 1️⃣1️⃣ Add description if not provided
        if (empty($data['description'])) {
            $data['description'] = "Trip for {$client->name} with driver {$driver->name}";
        }

        Log::info('mutateFormDataBeforeCreate: Data validated successfully', ['data' => $data]);
        return $data;
    }

    /**
     * Validate that client, driver, and vehicle belong to the authenticated user
     */
    private function validateResourceOwnership(array $data): void
    {
        $userId = $data['user_id'];

        // Check client exists and belongs to user
        $client = Client::where('user_id', $userId)->find($data['client_id']);
        if (!$client) {
            throw ValidationException::withMessages([
                'client_id' => 'Invalid client selected or client does not belong to your account.',
            ]);
        }

        // Check driver exists and belongs to user
        $driver = Driver::where('user_id', $userId)->find($data['driver_id']);
        if (!$driver) {
            throw ValidationException::withMessages([
                'driver_id' => 'Invalid driver selected or driver does not belong to your account.',
            ]);
        }

        // Check vehicle exists and belongs to user
        $vehicle = Vehicle::where('user_id', $userId)->find($data['vehicle_id']);
        if (!$vehicle) {
            throw ValidationException::withMessages([
                'vehicle_id' => 'Invalid vehicle selected or vehicle does not belong to your account.',
            ]);
        }
    }

    /**
     * Check for overlapping trips for both driver and vehicle
     */
    private function checkForOverlappingTrips(array $data, Carbon $start, Carbon $end): void
    {
        // More robust overlap detection using interval overlap logic
        $overlapQuery = Trip::where('user_id', $data['user_id'])
            ->where(function ($query) use ($start, $end) {
                $query->where(function ($q) use ($start, $end) {
                    // Case 1: Existing trip starts before new trip ends AND
                    // existing trip ends after new trip starts
                    $q->where('start_time', '<', $end)
                      ->where('end_time', '>', $start);
                });
            });

        // Check for driver conflicts
        $driverConflict = (clone $overlapQuery)->where('driver_id', $data['driver_id'])->exists();

        if ($driverConflict) {
            throw ValidationException::withMessages([
                'start_time' => 'The selected driver already has a trip scheduled during this time period.',
                'driver_id' => 'Driver conflict detected.',
            ]);
        }

        // Check for vehicle conflicts
        $vehicleConflict = (clone $overlapQuery)->where('vehicle_id', $data['vehicle_id'])->exists();

        if ($vehicleConflict) {
            throw ValidationException::withMessages([
                'start_time' => 'The selected vehicle is already booked during this time period.',
                'vehicle_id' => 'Vehicle conflict detected.',
            ]);
        }

        Log::info('Overlap check completed', [
            'driver_conflicts' => $driverConflict,
            'vehicle_conflicts' => $vehicleConflict,
        ]);
    }

    /**
     * Validate trip status
     */
    private function validateTripStatus(array $data): void
    {
        $validStatuses = array_column(TripStatus::cases(), 'value');
        
        if (!in_array($data['status'], $validStatuses)) {
            throw ValidationException::withMessages([
                'status' => 'Invalid trip status selected.',
            ]);
        }

        // Business rule: New trips should typically start as PLANNED
        if ($data['status'] === TripStatus::COMPLETED->value) {
            throw ValidationException::withMessages([
                'status' => 'New trips cannot be created with COMPLETED status.',
            ]);
        }
    }

    /**
     * Handle the actual record creation with proper error handling
     */
    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        Log::info('handleRecordCreation: Attempting to create trip', ['data' => $data]);
        
        try {
            // Create the trip record
            $record = static::getModel()::create($data);
            
            Log::info('handleRecordCreation: Trip created successfully', [
                'id' => $record->id, 
                'client' => $record->client->name ?? 'Unknown',
                'driver' => $record->driver->name ?? 'Unknown',
                'vehicle' => $record->vehicle->name ?? 'Unknown',
            ]);

            // Send success notification
            Notification::make()
                ->title('Trip Created Successfully')
                ->body("Trip scheduled for {$record->client->name} has been created.")
                ->success()
                ->duration(5000)
                ->send();

            return $record;
            
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('handleRecordCreation: Database error', [
                'error' => $e->getMessage(),
                'sql' => $e->getSql() ?? 'N/A',
                'bindings' => $e->getBindings() ?? [],
                'data' => $data
            ]);

            // Handle specific database errors
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                throw ValidationException::withMessages([
                    'general' => 'A trip with these details already exists.',
                ]);
            }

            Notification::make()
                ->title('Database Error')
                ->body('Failed to save trip to database. Please try again.')
                ->danger()
                ->duration(8000)
                ->send();

            throw $e;
            
        } catch (ValidationException $e) {
            Log::warning('handleRecordCreation: Validation error during creation', [
                'errors' => $e->errors(),
                'data' => $data
            ]);

            // Re-throw validation exceptions as-is
            throw $e;
            
        } catch (\Throwable $e) {
            Log::error('handleRecordCreation: Unexpected error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data
            ]);

            Notification::make()
                ->title('Unexpected Error')
                ->body('An unexpected error occurred while creating the trip.')
                ->danger()
                ->duration(8000)
                ->send();

            throw $e;
        }
    }

    /**
     * Handle successful record creation
     */
    protected function afterCreate(): void
    {
        Log::info('Trip creation process completed successfully');
        
        // Additional actions after successful creation can go here
        // For example: send notifications, update related records, etc.
    }

    /**
     * Get redirect URL after successful creation
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Get the form actions (customize buttons if needed)
     */
    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    /**
     * Customize the create button
     */
    protected function getCreateFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateFormAction()
            ->label('Create Trip')
            ->icon('heroicon-o-plus')
            ->requiresConfirmation()
            ->modalHeading('Confirm Trip Creation')
            ->modalDescription('Are you sure you want to create this trip with the selected details?')
            ->modalSubmitActionLabel('Yes, Create Trip');
    }

    /**
     * Get the page title
     */
    protected function getHeaderActions(): array
    {
        return [
            // Add any header actions if needed
        ];
    }

    /**
     * Customize page title
     */
    public function getTitle(): string
    {
        return 'Create New Trip';
    }

    /**
     * Add breadcrumbs
     */
    public function getBreadcrumbs(): array
    {
        return [
            '/admin/trips' => 'Trips',
            '/admin/trips/create' => 'Create',
        ];
    }
}