<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

trait RecordCreator
{
    public static function bootRecordCreator()
    {
        static::creating(function ($model) {
            if (Auth::check() && ! $model->created_by) {
                $model->created_by = Auth::id();
            }

            if (
                Auth::check()
                && property_exists($model, 'fillable')
                && in_array('company_id', $model->getFillable(), true)
                && ! $model->company_id
                && Auth::user()->company_id
            ) {
                $model->company_id = Auth::user()->company_id;
            }
        });
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
