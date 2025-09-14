<?php

namespace App\Filament\Admin\Resources\TripResource\Pages;

use App\Filament\Admin\Resources\TripResource;
use App\Services\TripValidationService;
use App\Services\TripCreationService;
use App\Models\{Trip, Driver, Vehicle};
use App\Enums\{TripStatus, EmploymentStatus};
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\{Auth, Log};
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class CreateTrip extends CreateRecord
{
    protected static string $resource = TripResource::class;

    public function mount(): void
    {
        Log::info('CreateTrip: Mounting create trip page');
        parent::mount();
        $this->setDefaultFormValues();
    }

    // FORM-LEVEL VALIDATION - Real-time validation as user interacts with form
    protected function getFormValidationRules(): array
    {
        return [
            'start_time' => [
                'required',
                'date',
                'after:now',
                function ($attribute, $value, $fail) {
                    $this->validateFormStartTime($value, $fail);
                },
            ],
            'end_time' => [
                'required',
                'date',
                'after:start_time',
                function ($attribute, $value, $fail) {
                    $this->validateFormEndTime($value, $fail);
                },
            ],
            'driver_id' => [
                'required',
                'exists:drivers,id',
                function ($attribute, $value, $fail) {
                    $this->validateFormDriver($value, $fail);
                },
            ],
            'vehicle_id' => [
                'required',
                'exists:vehicles,id',
                function ($attribute, $value, $fail) {
                    $this->validateFormVehicle($value, $fail);
                },
            ],
            'client_id' => [
                'required',
                'exists:clients,id',
            ],
            'status' => [
                'required',
                'in:' . implode(',', array_column(TripStatus::cases(), 'value')),
            ],
        ];
    }

    // FORM VALIDATION HELPERS - These run during form interaction
    private function validateFormStartTime($value, $fail): void
    {
        try {
            $start = Carbon::parse($value);
            
            // Business rule: No past trips (5-minute grace period)
            if ($start->lt(now()->subMinutes(5))) {
                $fail('Trip cannot be scheduled in the past.');
            }
        } catch (\Exception $e) {
            $fail('Invalid start time format.');
        }
    }

    private function validateFormEndTime($value, $fail): void
    {
        try {
            $startTime = $this->data['start_time'] ?? null;
            if (!$startTime) return;
            
            $start = Carbon::parse($startTime);
            $end = Carbon::parse($value);
            
            // Business rule: Maximum 24 hours duration
            if ($end->diffInHours($start) > 24) {
                $fail('Trip duration cannot exceed 24 hours.');
            }
        } catch (\Exception $e) {
            $fail('Invalid end time format.');
        }
    }

    private function validateFormDriver($driverId, $fail): void
    {
        $driver = Driver::where('user_id', Auth::id())->find($driverId);
        
        if (!$driver) {
            $fail('Driver does not belong to your account.');
            return;
        }

        // Check driver employment status
        $status = $driver->employment_status;
        $isActive = ($status instanceof EmploymentStatus) 
            ? $status === EmploymentStatus::ACTIVE
            : $status === 'active';
            
        if (!$isActive) {
            $statusValue = ($status instanceof EmploymentStatus) ? $status->value : (string) $status;
            $fail("Driver is not currently active. Status: {$statusValue}");
        }

        // Check for overlapping trips in real-time
        $this->checkFormDriverOverlaps($driverId, $fail);
    }

    private function validateFormVehicle($vehicleId, $fail): void
    {
        $vehicle = Vehicle::where('user_id', Auth::id())->find($vehicleId);
        
        if (!$vehicle) {
            $fail('Vehicle does not belong to your account.');
            return;
        }

        // Check driver-vehicle assignment
        $driverId = $this->data['driver_id'] ?? null;
        if ($driverId) {
            $driver = Driver::find($driverId);
            if ($driver && !$driver->vehicles()->where('vehicles.id', $vehicleId)->exists()) {
                $fail('This vehicle is not assigned to the selected driver.');
            }
        }

        // Check for overlapping trips in real-time
        $this->checkFormVehicleOverlaps($vehicleId, $fail);
    }

    private function checkFormDriverOverlaps($driverId, $fail): void
    {
        $startTime = $this->data['start_time'] ?? null;
        $endTime = $this->data['end_time'] ?? null;
        
        if (!$startTime || !$endTime) return;

        try {
            $start = Carbon::parse($startTime);
            $end = Carbon::parse($endTime);
            
            $conflicts = Trip::where('user_id', Auth::id())
                ->where('driver_id', $driverId)
                ->where('start_time', '<', $end)
                ->where('end_time', '>', $start)
                ->whereNotIn('status', [TripStatus::CANCELLED->value])
                ->first();
                
            if ($conflicts) {
                $fail("Driver has a conflicting trip from {$conflicts->start_time->format('M j, H:i')} to {$conflicts->end_time->format('M j, H:i')}.");
            }
        } catch (\Exception $e) {
            Log::error('Form validation driver overlap check failed', ['error' => $e->getMessage()]);
        }
    }

    private function checkFormVehicleOverlaps($vehicleId, $fail): void
    {
        $startTime = $this->data['start_time'] ?? null;
        $endTime = $this->data['end_time'] ?? null;
        
        if (!$startTime || !$endTime) return;

        try {
            $start = Carbon::parse($startTime);
            $end = Carbon::parse($endTime);
            
            $conflicts = Trip::where('user_id', Auth::id())
                ->where('vehicle_id', $vehicleId)
                ->where('start_time', '<', $end)
                ->where('end_time', '>', $start)
                ->whereNotIn('status', [TripStatus::CANCELLED->value])
                ->first();
                
            if ($conflicts) {
                $fail("Vehicle has a conflicting trip from {$conflicts->start_time->format('M j, H:i')} to {$conflicts->end_time->format('M j, H:i')}.");
            }
        } catch (\Exception $e) {
            Log::error('Form validation vehicle overlap check failed', ['error' => $e->getMessage()]);
        }
    }

    // SERVICE LAYER VALIDATION - Comprehensive validation before database operations
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        Log::info('CreateTrip: Mutating form data before create', ['original_data' => $data]);
        
        try {
            $data['user_id'] = Auth::id();
            Log::info('CreateTrip: Added user_id to data', ['user_id' => $data['user_id']]);
            
            // This is where the SERVICE LAYER validation happens
            // It performs comprehensive validation including:
            // - User authentication
            // - Date validation with business rules
            // - Resource validation (client, driver, vehicle ownership)
            // - Driver employment status
            // - Driver-vehicle assignment
            // - Comprehensive overlap detection
            // - Status validation
            // - Data enrichment
            $validatedData = app(TripValidationService::class)->validateAndEnrich($data);
            
            Log::info('CreateTrip: Data validation and enrichment completed', [
                'validated_data' => $validatedData
            ]);
            
            return $validatedData;
            
        } catch (ValidationException $e) {
            Log::error('CreateTrip: Validation failed in mutateFormDataBeforeCreate', [
                'errors' => $e->errors(),
                'message' => $e->getMessage(),
                'data' => $data
            ]);
            
            // Re-throw validation exceptions so Filament can handle them
            throw $e;
            
        } catch (\Throwable $e) {
            Log::error('CreateTrip: Unexpected error in mutateFormDataBeforeCreate', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data
            ]);
            
            // Convert to ValidationException for better user experience
            throw ValidationException::withMessages([
                'general' => 'An unexpected error occurred during validation: ' . $e->getMessage()
            ]);
        }
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        Log::info('CreateTrip: Starting record creation', ['data' => $data]);
        
        try {
            // Use app() helper instead of constructor injection
            $trip = app(TripCreationService::class)->create($data);
            
            Log::info('CreateTrip: Record creation completed successfully', [
                'trip_id' => $trip->id
            ]);
            
            return $trip;
            
        } catch (ValidationException $e) {
            Log::error('CreateTrip: Validation error in handleRecordCreation', [
                'errors' => $e->errors(),
                'data' => $data
            ]);
            
            // Show notification for validation errors
            Notification::make()
                ->title('Validation Error')
                ->body('Please check the form for errors and try again.')
                ->danger()
                ->duration(8000)
                ->send();
                
            throw $e;
            
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('CreateTrip: Database error in handleRecordCreation', [
                'error' => $e->getMessage(),
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
                'data' => $data
            ]);
            
            Notification::make()
                ->title('Database Error')
                ->body('Failed to save the trip. Please try again.')
                ->danger()
                ->duration(8000)
                ->send();
                
            throw ValidationException::withMessages([
                'general' => 'Database error occurred while creating the trip.'
            ]);
            
        } catch (\Throwable $e) {
            Log::error('CreateTrip: Unexpected error in handleRecordCreation', [
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
                
            throw ValidationException::withMessages([
                'general' => 'Unexpected error: ' . $e->getMessage()
            ]);
        }
    }

    private function sendCreateSuccessNotification(\App\Models\Trip $trip): void
    {
        try {
            Log::info('CreateTrip: Sending success notification', ['trip_id' => $trip->id]);
            
            // Load relationships if not already loaded
            $trip->loadMissing(['client', 'driver', 'vehicle']);
            
            Notification::make()
                ->title('🎉 Trip Created Successfully!')
                ->body(sprintf(
                    "Trip #%d has been scheduled successfully.\n" .
                    "Client: %s\n" .
                    "Driver: %s\n" .
                    "Start: %s",
                    $trip->id,
                    $trip->client?->name ?? 'Unknown',
                    $trip->driver?->name ?? 'Unknown',
                    $trip->start_time?->format('M j, Y \a\t g:i A') ?? 'Not set'
                ))
                ->success()
                ->duration(8000)
                ->actions([
                    \Filament\Notifications\Actions\Action::make('view')
                        ->button()
                        ->label('View Trip')
                        ->url($this->getResource()::getUrl('view', ['record' => $trip->id]))
                        ->openUrlInNewTab(false),
                    \Filament\Notifications\Actions\Action::make('edit')
                        ->button()
                        ->label('Edit Trip')
                        ->color('gray')
                        ->url($this->getResource()::getUrl('edit', ['record' => $trip->id]))
                        ->openUrlInNewTab(false)
                ])
                ->send();
                
            Log::info('CreateTrip: Success notification sent successfully');
        } catch (\Exception $e) {
            Log::error('CreateTrip: Failed to send success notification', [
                'error' => $e->getMessage(),
                'trip_id' => $trip->id ?? 'unknown'
            ]);
            
            // Fallback simple notification
            try {
                Notification::make()
                    ->title('Trip Created')
                    ->body('Your trip has been created successfully.')
                    ->success()
                    ->duration(5000)
                    ->send();
            } catch (\Exception $fallbackError) {
                Log::error('CreateTrip: Even fallback notification failed', [
                    'error' => $fallbackError->getMessage()
                ]);
            }
        }
    }

    private function setDefaultFormValues(): void
    {
        Log::info('CreateTrip: Setting default form values');
        
        try {
            $this->form->fill([
                'start_time' => now()->addHour()->format('Y-m-d H:i:s'),
                'end_time' => now()->addHours(2)->format('Y-m-d H:i:s'),
                'status' => TripStatus::PLANNED->value,
            ]);
            
            Log::info('CreateTrip: Default form values set successfully');
        } catch (\Exception $e) {
            Log::error('CreateTrip: Failed to set default form values', [
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function getCreateFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateFormAction()
            ->label('Create Trip')
            ->icon('heroicon-o-plus')
            ->requiresConfirmation()
            ->modalHeading('Confirm Trip Creation')
            ->modalDescription('Are you sure you want to create this trip?')
            ->modalSubmitActionLabel('Yes, Create Trip');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getTitle(): string
    {
        return 'Create New Trip';
    }

    public function getBreadcrumbs(): array
    {
        return [
            '/admin/trips' => 'Trips',
            '/admin/trips/create' => 'Create',
        ];
    }

    protected function onValidationError(ValidationException $exception): void
    {
        Log::error('CreateTrip: Validation error occurred', [
            'errors' => $exception->errors(),
            'message' => $exception->getMessage()
        ]);

        Notification::make()
            ->title('Validation Failed')
            ->body('Please check the form for errors and correct them.')
            ->danger()
            ->duration(8000)
            ->send();

        parent::onValidationError($exception);
    }
}