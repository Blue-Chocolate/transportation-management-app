<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use App\Enums\TripStatus;

class Trip extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $fillable = [
        'client_id',
        'driver_id',
        'vehicle_id',
        'start_time',
        'end_time',
        'description',
        'status',
        'company_id',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'status' => TripStatus::class,
    ];

    public function company()
    {
        return $this->belongsTo(Company::class)->select(['id', 'name']);
    }

    public function client()
    {
        return $this->belongsTo(Client::class)->select(['id', 'name', 'company_id']);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class)->select(['id', 'name', 'company_id']);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class)->select(['id', 'name', 'company_id']);
    }

    public function getVehicleTypeAttribute()
    {
        return $this->vehicle->vehicle_type ?? null;
    }

    protected static function booted()
    {
        static::saving(function ($trip) {
            // Ensure related records belong to the same company
            if ($trip->client && $trip->driver && $trip->vehicle) {
                $companyId = $trip->company_id ?? $trip->driver->company_id;
                if ($trip->client->company_id !== $companyId || $trip->driver->company_id !== $companyId || $trip->vehicle->company_id !== $companyId) {
                    throw ValidationException::withMessages([
                        'company_id' => 'Client, driver, and vehicle must belong to the same company.',
                    ]);
                }
                $trip->company_id = $companyId;
            }

            if ($trip->end_time && $trip->end_time <= $trip->start_time) {
                throw ValidationException::withMessages([
                    'end_time' => 'End time must be after start time.',
                ]);
            }

            $overlapCondition = fn ($q) => $q->whereNull('deleted_at')
                ->where('status', '!=', TripStatus::CANCELLED)
                ->where(function ($subQ) use ($trip) {
                    $subQ->where('start_time', '<', $trip->end_time)
                         ->where('end_time', '>', $trip->start_time);
                });

            if ($trip->driver_id && self::where('driver_id', $trip->driver_id)
                ->where('id', '!=', $trip->id)
                ->where($overlapCondition)
                ->exists()) {
                throw ValidationException::withMessages([
                    'driver_id' => 'Driver has an overlapping trip.',
                ]);
            }

            if ($trip->vehicle_id && self::where('vehicle_id', $trip->vehicle_id)
                ->where('id', '!=', $trip->id)
                ->where($overlapCondition)
                ->exists()) {
                throw ValidationException::withMessages([
                    'vehicle_id' => 'Vehicle has an overlapping trip.',
                ]);
            }

            if ($trip->driver && $trip->vehicle && !$trip->driver->vehicles->contains($trip->vehicle)) {
                throw ValidationException::withMessages([
                    'vehicle_id' => 'Vehicle must be assigned to the selected driver.',
                ]);
            }
        });
    }
}