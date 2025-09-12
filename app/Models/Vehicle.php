<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $fillable = [
        'name',
        'registration_number',
        'vehicle_type',
        'company_id',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class)->select(['id', 'name']);
    }

    public function trips()
    {
        return $this->hasMany(Trip::class)->select(['id', 'driver_id', 'vehicle_id', 'company_id']);
    }

    public function drivers()
    {
        return $this->belongsToMany(Driver::class, 'driver_vehicle')->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('deleted_at');
    }

    public function scopeForCompany(Builder $query, $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    protected static function booted()
    {
        static::saving(function ($vehicle) {
            if ($vehicle->registration_number && self::where('registration_number', $vehicle->registration_number)->where('id', '!=', $vehicle->getKey())->exists()) {
                throw new \InvalidArgumentException('Registration number must be unique.');
            }
        });
    }
}