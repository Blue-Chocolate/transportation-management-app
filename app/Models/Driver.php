<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use App\Enums\EmploymentStatus;
use Illuminate\Database\Eloquent\Builder;

class Driver extends Authenticatable
{
    use HasFactory, SoftDeletes, Notifiable;

    protected $guarded = ['id'];

    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
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
        'company_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'license_expiration' => 'date',
        'date_of_birth' => 'date',
        'hire_date' => 'date',
        'background_check_date' => 'date',
        'medical_certified' => 'boolean',
        'performance_rating' => 'decimal:2',
        'route_assignments' => 'array',
        'training_certifications' => 'array',
        'employment_status' => EmploymentStatus::class,
        'password' => 'hashed',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class)->select(['id', 'name']);
    }

    public function vehicles()
    {
        return $this->belongsToMany(Vehicle::class, 'driver_vehicle')->withTimestamps();
    }

    public function trips()
    {
        return $this->hasMany(Trip::class)->select(['id', 'driver_id', 'vehicle_id', 'company_id']);
    }

    

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'driver' && $this->employment_status === EmploymentStatus::ACTIVE;
    }

    public function scopeForCompany(Builder $query, $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    protected static function booted()
    {
        static::saving(function ($driver) {
            if ($driver->license_expiration && $driver->license_expiration->isPast()) {
                throw new \InvalidArgumentException('License expiration must be a future date.');
            }

            if ($driver->email && self::where('email', $driver->email)->where('id', '!=', $driver->getKey())->exists()) {
                throw new \InvalidArgumentException('Email must be unique.');
            }

            if ($driver->employment_status === EmploymentStatus::ACTIVE && !$driver->background_check_date) {
                throw new \InvalidArgumentException('Active drivers must have a background check date.');
            }
        });
    }
}