<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToCompany;

class TestModel extends Model
{
    use BelongsToCompany;
}
