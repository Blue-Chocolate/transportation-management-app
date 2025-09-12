<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;

class Client extends Authenticatable
{
    use HasFactory, SoftDeletes, Notifiable;

    protected $guarded = ['id'];

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'company_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }
public function company()
    {
        return $this->belongsTo(Company::class)->select(['id', 'name']);
    }

    public function trips()
    {
        return $this->hasMany(Trip::class)->select(['id', 'driver_id', 'vehicle_id', 'company_id']);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'client';
    }

    public function scopeForCompany(Builder $query, $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }
}