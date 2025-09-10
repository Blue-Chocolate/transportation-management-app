<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Enums\TripStatus;
use Illuminate\Support\Facades\DB;

class Trip extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
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

    /**
     * Relationships
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

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

    /**
     * Booted model events for overlap validation
     */
    protected static function booted()
    {
        static::saving(function ($trip) {
            if ($trip->end_time <= $trip->start_time) {
                throw new \Illuminate\Validation\ValidationException('End time must be after start time.');
            }
  if ($trip->vehicle && $trip->vehicle->vehicle_type) {
    $trip->vehicle_type = $trip->vehicle->vehicle_type;
} else {
    $trip->vehicle_type = 'Car'; // Fallback to default
}
            $driverConflict = self::where('driver_id', $trip->driver_id)
                ->where('id', '!=', $trip->id) // Exclude the current trip when updating
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
                throw new \InvalidArgumentException('Driver has an overlapping trip.');
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
                throw new \InvalidArgumentException('Vehicle has an overlapping trip.');
            }
        });
    }
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }
}