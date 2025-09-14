<?php
namespace App\Listeners;

use App\Events\TripCreated;
use Illuminate\Support\Facades\Log;

class SendTripNotifications
{
    public function handle(TripCreated $event): void
    {
        $trip = $event->trip;
        
        // Send email notifications to client and driver
        // Mail::to($trip->client->email)->send(new TripConfirmation($trip));
        // Mail::to($trip->driver->email)->send(new NewTripAssignment($trip));
        
        Log::info('Trip notifications would be sent', ['trip_id' => $trip->id]);
    }
}