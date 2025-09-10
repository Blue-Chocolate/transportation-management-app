<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name'];

    public function clients() { return $this->hasMany(Client::class); }
    public function drivers() { return $this->hasMany(Driver::class); }
    public function vehicles() { return $this->hasMany(Vehicle::class); }
    public function trips() { return $this->hasMany(Trip::class); }

    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }
}