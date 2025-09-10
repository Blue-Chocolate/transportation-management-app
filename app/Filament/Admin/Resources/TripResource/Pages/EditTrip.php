<?php

namespace App\Filament\Admin\Resources\TripResource\Pages;

use App\Filament\Admin\Resources\TripResource;
use App\Models\Driver;
use App\Models\Trip;
use Carbon\Carbon;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditTrip extends EditRecord
{
    protected static string $resource = TripResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $start = Carbon::parse($data['start_time']);
        $end   = Carbon::parse($data['end_time']);

        // ✅ Check vehicle belongs to driver
        $driver = Driver::find($data['driver_id']);
        if (! $driver || ! $driver->vehicles()->where('vehicle_id', $data['vehicle_id'])->exists()) {
            throw ValidationException::withMessages([
                'vehicle_id' => 'This vehicle is not assigned to the selected driver.',
            ]);
        }

        // ✅ Check overlap conflicts (ignore current trip ID)
        $conflict = Trip::where('id', '!=', $this->record->id)
            ->where(function ($q) use ($data) {
                $q->where('driver_id', $data['driver_id'])
                  ->orWhere('vehicle_id', $data['vehicle_id']);
            })
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                'start_time' => 'Driver or vehicle already booked in this time range.',
            ]);
        }

        return $data;
    }
}
