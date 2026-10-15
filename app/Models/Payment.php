<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\RecordCreator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use BelongsToCompany, HasFactory, RecordCreator;

    protected $fillable = [
        'company_id',
        'rent_id',
        'payment_date',
        'due_date',
        'amount',
        'payment_method',
        'status',
        'reference',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'due_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function rent()
    {
        return $this->belongsTo(Rent::class);
    }

    public function tenant()
    {
        return $this->hasOneThrough(Tenant::class, Rent::class);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year);
    }

    public function scopeLastMonth($query)
    {
        return $query->whereMonth('payment_date', now()->subMonth()->month)
            ->whereYear('payment_date', now()->subMonth()->year);
    }
}
