<?php

namespace App\Models;

use App\Enums\TripStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class Trip extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id',
        'driver_id',
        'vehicle_id',
        'vehicle_type',
        'start_time',
        'end_time',
        'description',
        'status',
        'user_id',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time'   => 'datetime',
        'status'     => TripStatus::class,
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    protected static function booted()
    {
        // Run before creating a new model (once)
        static::creating(function (Trip $trip) {
            // Automatically fill vehicle_type based on vehicle_id if not set
            if ($trip->vehicle_id && empty($trip->vehicle_type)) {
                $vehicle = Vehicle::find($trip->vehicle_id);
                if ($vehicle) {
                    $trip->vehicle_type = $vehicle->vehicle_type;
                }
            }
        });

        // Run on both creating & updating
        static::saving(function (Trip $trip) {
            // Ensure start_time and end_time are Carbon instances
            $start = $trip->start_time instanceof Carbon ? $trip->start_time : Carbon::parse($trip->start_time);
            $end   = $trip->end_time instanceof Carbon ? $trip->end_time : Carbon::parse($trip->end_time);

            // Validate end_time > start_time
            if ($end->lte($start)) {
                throw ValidationException::withMessages([
                    'end_time' => 'End time must be after start time.',
                ]);
            }

            // Driver overlapping trips
            $driverConflict = self::where('driver_id', $trip->driver_id)
                ->where('id', '!=', $trip->id ?? 0)
                ->where(function ($query) use ($start, $end) {
                    $query->whereBetween('start_time', [$start, $end])
                          ->orWhereBetween('end_time', [$start, $end])
                          ->orWhere(function ($q) use ($start, $end) {
                              $q->where('start_time', '<=', $start)
                                ->where('end_time', '>=', $end);
                          });
                })
                ->exists();

            if ($driverConflict) {
                throw ValidationException::withMessages([
                    'start_time' => 'Driver has an overlapping trip.',
                ]);
            }

            // Vehicle overlapping trips
            $vehicleConflict = self::where('vehicle_id', $trip->vehicle_id)
                ->where('id', '!=', $trip->id ?? 0)
                ->where(function ($query) use ($start, $end) {
                    $query->whereBetween('start_time', [$start, $end])
                          ->orWhereBetween('end_time', [$start, $end])
                          ->orWhere(function ($q) use ($start, $end) {
                              $q->where('start_time', '<=', $start)
                                ->where('end_time', '>=', $end);
                          });
                })
                ->exists();

            if ($vehicleConflict) {
                throw ValidationException::withMessages([
                    'start_time' => 'Vehicle has an overlapping trip.',
                ]);
            }

            // Sync vehicle_type with related vehicle (use existing relation if loaded)
            if ($trip->vehicle) {
                // Make sure to use the correct property name 'vehicle_type'
                $trip->vehicle_type = $trip->vehicle->vehicle_type;
            }
        });
    }
}
