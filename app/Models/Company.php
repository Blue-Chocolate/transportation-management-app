<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\{Client, Driver, Vehicle, Trip};


class Company extends Model
{   use \Illuminate\Database\Eloquent\Factories\HasFactory;
    
    public function clients() { return $this->hasMany(Client::class); }
    public function drivers() { return $this->hasMany(Driver::class); }
    public function vehicles() { return $this->hasMany(Vehicle::class); }
    public function trips() { return $this->hasMany(Trip::class); }
}

