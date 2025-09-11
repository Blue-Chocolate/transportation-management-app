<?php

namespace App\Models;

use App\Enums\TripStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

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
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'status' => TripStatus::class,
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
        static::saving(function ($trip) {
            // Validate end_time > start_time
            if ($trip->end_time <= $trip->start_time) {
                throw ValidationException::withMessages([
                    'end_time' => 'End time must be after start time.',
                ]);
            }

            // Check for driver overlap
            $driverConflict = self::where('driver_id', $trip->driver_id)
                ->where('id', '!=', $trip->id)
                ->where(function ($query) use ($trip) {
                    $query->whereBetween('start_time', [$trip->start_time, $trip->end_time])
                          ->orWhereBetween('end_time', [$trip->start_time, $trip->end_time])
                          ->orWhere(function ($q) use ($trip) {
                              $q->where('start_time', '<=', $trip->start_time)
                                ->where('end_time', '>=', $trip->end_time);
                          });
                })
                ->exists();

            if ($driverConflict) {
                throw ValidationException::withMessages([
                    'start_time' => 'Driver has an overlapping trip.',
                ]);
            }

            // Check for vehicle overlap
            $vehicleConflict = self::where('vehicle_id', $trip->vehicle_id)
                ->where('id', '!=', $trip->id)
                ->where(function ($query) use ($trip) {
                    $query->whereBetween('start_time', [$trip->start_time, $trip->end_time])
                          ->orWhereBetween('end_time', [$trip->start_time, $trip->end_time])
                          ->orWhere(function ($q) use ($trip) {
                              $q->where('start_time', '<=', $trip->start_time)
                                ->where('end_time', '>=', $trip->end_time);
                          });
                })
                ->exists();

            if ($vehicleConflict) {
                throw ValidationException::withMessages([
                    'start_time' => 'Vehicle has an overlapping trip.',
                ]);
            }

            // Sync vehicle_type with vehicle
            if ($trip->vehicle) {
                $trip->vehicle_type = $trip->vehicle->type;
            }
        });
    }
}