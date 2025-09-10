<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Company;
use App\Models\Trip;

class Client extends Authenticatable implements FilamentUser
{
    use HasFactory, SoftDeletes;

    /**
     * Determine if the client can access a given Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'client';
    }

    /**
     * Get the company that the client belongs to.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the trips associated with the client.
     */
    public function trips()
    {
        return $this->hasMany(Trip::class);
    }

    /**
     * Fillable attributes.
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'company_id',
        'password',
    ];

    /**
     * Hidden attributes for arrays.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Automatically hash passwords when setting them.
     */
    public function setPasswordAttribute($value)
    {
        if ($value) {
            $this->attributes['password'] = bcrypt($value);
        }
    }

    /**
     * Scope to get only active clients (not soft-deleted).
     */
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * Enable timestamps (created_at, updated_at) by default.
     */
    public $timestamps = true;
}
