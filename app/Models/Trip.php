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
            $conflict = self::where('driver_id', $trip->driver_id)
                ->where(function ($query) use ($trip) {
                    $query->whereBetween('start_time', [$trip->start_time, $trip->end_time])
                          ->orWhereBetween('end_time', [$trip->start_time, $trip->end_time])
                          ->orWhere(function ($q) use ($trip) {
                              $q->where('start_time', '<=', $trip->start_time)
                                ->where('end_time', '>=', $trip->end_time);
                          });
                })
                ->exists();

            if ($conflict) {
                throw new \InvalidArgumentException('Driver has an overlapping trip.');
            }

            $vehicleConflict = self::where('vehicle_id', $trip->vehicle_id)
                ->where(function ($query) use ($trip) {
                    $query->whereBetween('start_time', [$trip->start_time, $trip->end_time])
                          ->orWhereBetween('end_time', [$trip->start_time, $trip->end_time])
                          ->orWhere(function ($q) use ($trip) {
                              $q->where('start_time', '<=', $trip->start_time)
                                ->where('end_time', '>=', $trip->end_time);
                          });
                        })
                        protected $casts = [
    'status' => TripStatus::class,
];