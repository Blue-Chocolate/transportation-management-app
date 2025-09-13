<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use App\Enums\TripStatus;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Traits\BelongsToCompany;

class Trip extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

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

    public function scopeForCompany(Builder $query, $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', TripStatus::ACTIVE);
    }

    protected static function booted()
    {
        static::saving(function ($trip) {
            if (!$trip->company_id && $trip->driver) {
                $trip->company_id = $trip->driver->company_id;
            }

            if ($trip->end_time <= $trip->start_time) {
                throw ValidationException::withMessages([
                    'end_time' => 'End time must be after start time.',
                ]);
            }

            $overlapCondition = function ($q) use ($trip) {
                return $q->whereNull('deleted_at')
                         ->where('status', '!=', TripStatus::CANCELLED)
                         ->where(function ($subQ) use ($trip) {
                             $subQ->where('start_time', '<', $trip->end_time)
                                  ->where('end_time', '>', $trip->start_time);
                         });
            };

            $driverConflict = self::where('driver_id', $trip->driver_id)
                                  ->where('id', '!=', $trip->getKey())
                                  ->where($overlapCondition)
                                  ->exists();

            if ($driverConflict) {
                throw ValidationException::withMessages([
                    'driver_id' => 'Driver has an overlapping trip.',
                ]);
            }

            $vehicleConflict = self::where('vehicle_id', $trip->vehicle_id)
                                   ->where('id', '!=', $trip->getKey())
                                   ->where($overlapCondition)
                                   ->exists();

            if ($vehicleConflict) {
                throw ValidationException::withMessages([
                    'vehicle_id' => 'Vehicle has an overlapping trip.',
                ]);
            }

            if ($trip->driver && $trip->vehicle && !$trip->driver->vehicles->contains($trip->vehicle)) {
                throw ValidationException::withMessages([
                    'vehicle_id' => 'Vehicle must be assigned to the selected driver.',
                ]);
            }

            if ($trip->driver && $trip->company_id && $trip->driver->company_id !== $trip->company_id) {
                throw ValidationException::withMessages([
                    'driver_id' => 'Driver must belong to the same company.',
                ]);
            }

            if ($trip->vehicle && $trip->company_id && $trip->vehicle->company_id !== $trip->company_id) {
                throw ValidationException::withMessages([
                    'vehicle_id' => 'Vehicle must belong to the same company.',
                ]);
            }

            if ($trip->client && $trip->company_id && $trip->client->company_id !== $trip->company_id) {
                throw ValidationException::withMessages([
                    'client_id' => 'Client must belong to the same company.',
                ]);
            }
        });
    }
}