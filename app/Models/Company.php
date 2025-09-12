<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
    ];

    // Relationships

    public function users()
    {
        return $this->hasMany(User::class)->select(['id', 'name', 'email', 'role', 'company_id']);
    }

    public function clients()
    {
        return $this->hasMany(Client::class)->select(['id', 'name', 'company_id']);
    }

    public function drivers()
    {
        return $this->hasMany(Driver::class)->select(['id', 'name', 'company_id']);
    }

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class)->select(['id', 'name', 'company_id']);
    }

    public function trips()
    {
        return $this->hasMany(Trip::class)->select(['id', 'driver_id', 'vehicle_id', 'company_id', 'status']);
    }

    // Helper to count active trips
    public function activeTripsCount(): int
    {
        return $this->trips()->where('status', \App\Enums\TripStatus::ACTIVE)->count();
    }

    // Optional: Filament label for display
    public function label(): string
    {
        return $this->name;
    }
}
