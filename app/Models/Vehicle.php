<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // Add for consistency
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'registration_number',
        'vehicle_type',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function trips()
    {
        return $this->hasMany(Trip::class);
    }

    public function drivers() // Change to belongsToMany for symmetry
    {
        return $this->belongsToMany(Driver::class)->withTimestamps();
    }

    public function scopeActive($query) // Add consistent scope
    {
        return $query->whereNull('deleted_at');
    }
}