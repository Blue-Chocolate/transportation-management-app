<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{ use \Illuminate\Database\Eloquent\Factories\HasFactory;
    public function company() { return $this->belongsTo(Company::class); }
    public function client() { return $this->belongsTo(Client::class); }
    public function driver() { return $this->belongsTo(Driver::class); }
    public function vehicle() { return $this->belongsTo(Vehicle::class); }
}

