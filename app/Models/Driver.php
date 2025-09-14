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
     * The table associated with the model.
     */
    protected $table = 'drivers';

    /**
     * Fillable attributes.
     */
    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
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
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
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
        'route_assignments'       => 'array',
        'training_certifications' => 'array',
        'email_verified_at'       => 'datetime',
        'password'                => 'hashed',
    ];

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function vehicles()
    {
        return $this->belongsToMany(\App\Models\Vehicle::class, 'driver_vehicle')
                    ->withTimestamps();
    }

    public function trips()
    {
        return $this->hasMany(\App\Models\Trip::class);
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
        return $query->whereNull('deleted_at')
                    ->where('employment_status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('employment_status', 'inactive');
    }

    /**
     * Check if driver is active
     */
    public function isActive(): bool
    {
        return $this->employment_status === 'active';
    }

    /**
     * Check if driver is inactive
     */
    public function isInactive(): bool
    {
        return $this->employment_status === 'inactive';
    }

    /**
     * Model events for validation
     */
    protected static function booted()
    {
        static::saving(function ($driver) {
            // Ensure license_expiration is in the future when provided
            if ($driver->license_expiration && $driver->license_expiration->isPast()) {
                throw new \InvalidArgumentException('License expiration must be a future date.');
            }

            // Ensure email uniqueness when provided
            if ($driver->email) {
                $query = self::where('email', $driver->email);
                if ($driver->exists) {
                    $query->where('id', '!=', $driver->id);
                }
                if ($query->exists()) {
                    throw new \InvalidArgumentException('Email must be unique.');
                }
            }
        });
    }

    /**
     * Get the name of the unique identifier for the user.
     */
    public function getAuthIdentifierName()
    {
        return 'id';
    }

    /**
     * Get the unique identifier for the user.
     */
    public function getAuthIdentifier()
    {
        return $this->{$this->getAuthIdentifierName()};
    }

    /**
     * Get the password for the user.
     */
    public function getAuthPassword()
    {
        return $this->password;
    }

    /**
     * Get the token value for the "remember me" session.
     */
    public function getRememberToken()
    {
        return $this->remember_token;
    }

    /**
     * Set the token value for the "remember me" session.
     */
    public function setRememberToken($value)
    {
        $this->remember_token = $value;
    }

    /**
     * Get the column name for the "remember me" token.
     */
    public function getRememberTokenName()
    {
        return 'remember_token';
    }
}