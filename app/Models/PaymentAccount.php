<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentAccount extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id',
        'label',
        'bank_name',
        'account_name',
        'account_number',
        'branch',
        'instructions',
        'is_primary',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderByDesc('is_primary')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->label ?: $this->bank_name;
    }
}
