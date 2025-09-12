<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class BaseCompany extends Model
{
    /**
     * Boot the model and add a global scope for company.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('company', function ($query) {
            /** @var \Illuminate\Contracts\Auth\Authenticatable|null $user */
            $user = Auth::user();

            if ($user && property_exists($user, 'company_id') && $user->company_id) {
                $query->where('company_id', $user->company_id);
            }
        });
    }
}
