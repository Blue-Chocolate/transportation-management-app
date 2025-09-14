<?php

namespace App\Services;

use App\Models\Trip;
use App\Events\TripCreated; // You'll need to create this event
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\{Log, DB};
use Illuminate\Validation\ValidationException;

class TripCreationService
{
    public function create(array $data): Trip
    {
        Log::info('TripCreationService: Starting trip creation', ['data' => $data]);
        
        return DB::transaction(function () use ($data) {
            try {
                Log::info('TripCreationService: About to create trip in database');
                
                $trip = Trip::create($data);
                
                Log::info('TripCreationService: Trip created successfully in database', [
                    'trip_id' => $trip->id,
                    'trip_data' => $trip->toArray()
                ]);
                
                // Load relationships for logging
                $trip->load(['client', 'driver', 'vehicle']);
                
                // Fire event for other parts of the system
                // event(new TripCreated($trip));
                
                $this->sendSuccessNotification($trip);
                
                Log::info('TripCreationService: Trip creation completed successfully', [
                    'trip_id' => $trip->id,
                    'client' => $trip->client?->name ?? 'N/A',
                    'driver' => $trip->driver?->name ?? 'N/A',
                    'vehicle' => $trip->vehicle?->name ?? 'N/A',
                ]);
                
                return $trip;
                
            } catch (\Illuminate\Database\QueryException $e) {
                Log::error('TripCreationService: Database error during trip creation', [
                    'error' => $e->getMessage(),
                    'sql' => $e->getSql(),
                    'bindings' => $e->getBindings(),
                    'data' => $data
                ]);
                $this->handleDatabaseError($e, $data);
                throw $e;
            } catch (ValidationException $e) {
                Log::error('TripCreationService: Validation error during trip creation', [
                    'error' => $e->getMessage(),
                    'errors' => $e->errors(),
                    'data' => $data
                ]);
                throw $e;
            } catch (\Throwable $e) {
                Log::error('TripCreationService: Unexpected error during trip creation', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'data' => $data
                ]);
                $this->handleUnexpectedError($e, $data);
                throw $e;
            }
        });
    }

    private function sendSuccessNotification(Trip $trip): void
    {
        try {
            Log::info('TripCreationService: Sending success notification');
            
            $clientName = $trip->client?->name ? $trip->client->name : 'Unknown Client';
            Notification::make()
                ->title('Trip Created Successfully')
                ->body("Trip #{$trip->id} scheduled for {$clientName} has been created.")
                ->success()
                ->duration(5000)
                ->actions([
                    \Filament\Notifications\Actions\Action::make('view')
                        ->button()
                        ->url(route('filament.admin.resources.trips.view', $trip))
                ])
                ->send();
                
            Log::info('TripCreationService: Success notification sent');
        } catch (\Exception $e) {
            Log::error('TripCreationService: Failed to send success notification', [
                'error' => $e->getMessage()
            ]);
            // Don't throw - notification failure shouldn't break trip creation
        }
    }

    private function handleDatabaseError(\Illuminate\Database\QueryException $e, array $data): void
    {
        Log::error('TripCreationService: Handling database error', [
            'error' => $e->getMessage(),
            'sql_state' => $e->errorInfo[0] ?? null,
            'error_code' => $e->errorInfo[1] ?? null,
            'data' => $data
        ]);

        // Handle specific database errors
        $errorMessage = $e->getMessage();
        
        if (str_contains($errorMessage, 'Duplicate entry')) {
            throw ValidationException::withMessages([
                'general' => 'A trip with these details already exists.',
            ]);
        }
        
        if (str_contains($errorMessage, 'overlapping trip')) {
            throw ValidationException::withMessages([
                'general' => 'This trip conflicts with an existing trip. Please check the schedule.',
            ]);
        }

        // Check for foreign key constraint errors
        if (str_contains($errorMessage, 'foreign key constraint')) {
            throw ValidationException::withMessages([
                'general' => 'Invalid reference to client, driver, or vehicle. Please check your selections.',
            ]);
        }

        try {
            Notification::make()
                ->title('Database Error')
                ->body('Failed to save trip to database. Please try again.')
                ->danger()
                ->duration(8000)
                ->send();
        } catch (\Exception $notificationError) {
            Log::error('TripCreationService: Failed to send database error notification', [
                'notification_error' => $notificationError->getMessage()
            ]);
        }
    }

    private function handleUnexpectedError(\Throwable $e, array $data): void
    {
        Log::error('TripCreationService: Handling unexpected error', [
            'error_type' => get_class($e),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'data' => $data
        ]);

        try {
            Notification::make()
                ->title('Unexpected Error')
                ->body('An unexpected error occurred while creating the trip.')
                ->danger()
                ->duration(8000)
                ->send();
        } catch (\Exception $notificationError) {
            Log::error('TripCreationService: Failed to send unexpected error notification', [
                'notification_error' => $notificationError->getMessage()
            ]);
        }
    }
}