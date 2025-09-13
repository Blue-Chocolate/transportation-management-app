<?php

namespace App\Filament\Client\Resources\TripsResource\Pages;

use App\Filament\Client\Resources\TripsResource;
use App\Models\Trip;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Carbon\Carbon;

class CreateTrips extends CreateRecord
{
    protected static string $resource = TripsResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // ✅ Ensure client is logged in
        $clientId = auth('client')->id();
        if (!$clientId) {
            Notification::make()
                ->title('Unauthorized')
                ->body('You must be logged in as a client to create a trip.')
                ->danger()
                ->send();

            $this->halt(); // Stops form submission
        }
        $data['client_id'] = $clientId;

        $start = Carbon::parse($data['start_time']);
        $end   = Carbon::parse($data['end_time']);
        $now   = now();

        // ✅ Validate start_time: not more than 1 minute in the past
        if ($start->lt((clone $now)->subMinute())) {
            Notification::make()
                ->title('Invalid Start Time')
                ->body('The start time cannot be more than 1 minute in the past.')
                ->danger()
                ->send();

            $this->halt();
        }

        // ✅ Validate start_time: not more than 3 weeks in the future
        if ($start->gt((clone $now)->addWeeks(3))) {
            Notification::make()
                ->title('Invalid Start Time')
                ->body('The start time cannot be more than 3 weeks in the future.')
                ->danger()
                ->send();

            $this->halt();
        }

        // ✅ Ensure no overlapping trips (driver OR vehicle)
        $conflict = Trip::where(function ($q) use ($data) {
                $q->where('driver_id', $data['driver_id'])
                  ->orWhere('vehicle_id', $data['vehicle_id']);
            })
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_time', [$start, $end])
                  ->orWhereBetween('end_time', [$start, $end])
                  ->orWhere(function ($q2) use ($start, $end) {
                      $q2->where('start_time', '<=', $start)
                         ->where('end_time', '>=', $end);
                  });
            })
            ->exists();

        if ($conflict) {
            Notification::make()
                ->title('Schedule Conflict')
                ->body('Driver or vehicle is already booked during this time range.')
                ->danger()
                ->send();

            $this->halt();
        }

        return $data; // ✅ Pass clean data to Filament so Trip::create($data) runs
    }

    protected function afterCreate(): void
    {
        Notification::make()
            ->title('Trip Created')
            ->body('Your trip has been successfully scheduled!')
            ->success()
            ->send();
    }
}
