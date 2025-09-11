<?php

namespace App\Filament\Client\Resources\TripsResource\Pages;

use App\Filament\Client\Resources\TripsResource;
use App\Models\Trip;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditTrip extends EditRecord
{
    protected static string $resource = TripsResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['client_id'] = auth('client')->id();

        // prevent overlaps when editing
        $start = \Carbon\Carbon::parse($data['start_time']);
        $end   = \Carbon\Carbon::parse($data['end_time']);

        $conflict = Trip::where(function ($q) use ($data) {
                $q->where('driver_id', $data['driver_id'])
                  ->orWhere('vehicle_id', $data['vehicle_id']);
            })
            ->where('id', '!=', $this->record->id) // exclude current trip
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
            throw ValidationException::withMessages([
                'start_time' => 'Driver or vehicle already booked in this time range.',
            ]);
        }

        return $data;
    }
}
