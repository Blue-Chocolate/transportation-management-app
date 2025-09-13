<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Traits\BelongsToCompany;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

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
            if (!$vehicle->company_id) {
                if (\Illuminate\Support\Facades\Auth::check() && \Illuminate\Support\Facades\Auth::user()->company_id) {
                    $vehicle->company_id = \Illuminate\Support\Facades\Auth::user()->company_id;
                } else {
                    throw (\Illuminate\Validation\ValidationException::withMessages([
                        'company_id' => 'The company_id field is required.',
                    ]));
                }
            }

            if (
                $vehicle->registration_number &&
                self::where('registration_number', $vehicle->registration_number)
                    ->where('id', '!=', $vehicle->getKey())
                    ->exists()
            ) {
                throw (\Illuminate\Validation\ValidationException::withMessages([
                    'registration_number' => 'Registration number must be unique.',
                ]));
            }
        });
    }
}