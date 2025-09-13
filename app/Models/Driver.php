<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class Driver extends Authenticatable implements FilamentUser
{
    use HasFactory, SoftDeletes;

    /**
     * Fillable attributes.
     */
    protected $fillable = [
        'name',
        'phone',
        'email',
                'user_id',

        'emergency_contact',
        'license',
        'license_expiration',
        'date_of_birth',
        'address',
        'hire_date',
        'employment_status',
        'route_assignments',
        'performance_rating',
        'medical_certified',
        'background_check_date',
        'profile_photo',
        'notes',
        'insurance_info',
        'training_certifications',
    ];

    /**
     * Attribute casts.
     */
    protected $casts = [
        'license_expiration'      => 'date',
        'date_of_birth'           => 'date',
        'hire_date'               => 'date',
        'background_check_date'   => 'date',
        'medical_certified'       => 'boolean',
        'performance_rating'      => 'decimal:2',
        'route_assignments'       => 'array', // JSON
        'training_certifications' => 'array', // JSON
        'employment_status'       => \App\Enums\EmploymentStatus::class, // Define enum
    ];

    /**
     * Relationships
     */
    

    public function vehicles()
    {
        // Assumes driver_vehicle pivot table with timestamps
        return $this->belongsToMany(Vehicle::class)->withTimestamps();
    }

    public function trips()
    {
        return $this->hasMany(Trip::class);
    }

    /**
     * Filament panel access
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'driver';
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * Model events for validation
     */
    protected static function booted()
    {
        static::saving(function ($driver) {
            // Ensure license_expiration is in the future
            if ($driver->license_expiration && $driver->license_expiration->isPast()) {
                throw new \InvalidArgumentException('License expiration must be a future date.');
            }

            // Ensure email uniqueness
            if ($driver->email && self::where('email', $driver->email)->where('id', '!=', $driver->id)->exists()) {
                throw new \InvalidArgumentException('Email must be unique.');
            }
        });
    }
// public function setPasswordAttribute($value)
// {
//     if ($value) {
//         $this->attributes['password'] = bcrypt($value);
//     }
// }   

}
