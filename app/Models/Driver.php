<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'phone',
        'email',
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

    protected $casts = [
        'license_expiration'      => 'date',
        'date_of_birth'           => 'date',
        'hire_date'               => 'date',
        'background_check_date'   => 'date',
        'medical_certified'       => 'boolean',
        'performance_rating'      => 'decimal:2',
        'route_assignments'       => 'array',   // stored as JSON
        'training_certifications' => 'array',   // stored as JSON
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function vehicles()
    {
        return $this->belongsToMany(Vehicle::class)->withTimestamps();
    }

    public function trips()
    {
        return $this->hasMany(Trip::class);
    }
}
