<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    public function company() { return $this->belongsTo(Company::class); }
    public function trips() { return $this->hasMany(Trip::class); }
}
