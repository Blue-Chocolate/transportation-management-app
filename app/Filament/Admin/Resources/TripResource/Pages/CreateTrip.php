<?php

namespace App\Filament\Admin\Resources\TripResource\Pages;

use App\Filament\Admin\Resources\TripResource;
use App\Services\TripValidationService;
use App\Services\TripCreationService;
use App\Enums\TripStatus;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\{Auth, Log};
use Illuminate\Validation\ValidationException;

class CreateTrip extends CreateRecord
{
    protected static string $resource = TripResource::class;

    public function mount(): void
    {
        Log::info('CreateTrip: Mounting create trip page');
        parent::mount();
        $this->setDefaultFormValues();
    }

     protected function mutateFormDataBeforeCreate(array $data): array
    {
        Log::info('CreateTrip: Mutating form data before create', ['original_data' => $data]);
        
        try {
            $data['user_id'] = Auth::id();
            Log::info('CreateTrip: Added user_id to data', ['user_id' => $data['user_id']]);
            
            // Use app() helper instead of constructor injection
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