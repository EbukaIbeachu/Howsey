<?php

namespace App\Traits;

use App\Support\CurrentCompany;

trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        static::creating(function ($model) {
            if (! $model->company_id && app()->bound(CurrentCompany::class)) {
                $model->company_id = app(CurrentCompany::class)->id();
            }
        });

        static::addGlobalScope('company', function ($builder) {
            if (! app()->bound(CurrentCompany::class)) {
                return;
            }

            if (app()->runningInConsole() && ! app()->runningUnitTests()) {
                return;
            }

            $context = app(CurrentCompany::class);

            if (! $context->has()) {
                return;
            }

            $builder->where(
                $builder->getModel()->getTable().'.company_id',
                $context->id()
            );
        });
    }

    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->withoutGlobalScope('company')->where($this->getTable().'.company_id', $companyId);
    }
}
