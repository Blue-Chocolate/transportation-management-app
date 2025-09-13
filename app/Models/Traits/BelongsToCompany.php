<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        // Apply global scope automatically
        static::addGlobalScope('company', function (Builder $builder) {
            if (Auth::check()) {
                $builder->where('company_id', Auth::user()->company_id);
            }
        });

        // Automatically assign company_id on creating
        static::creating(function (Model $model) {
            if (Auth::check() && empty($model->company_id)) {
                $model->company_id = Auth::user()->company_id;
            }
        });
    }

    // Relation to company
    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    // Query without the global scope
    public static function withoutCompanyScope(): Builder
    {
        return (new static())->newQueryWithoutScope('company');
    }
}
