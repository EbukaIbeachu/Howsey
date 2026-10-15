<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CompanySubscriptionPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'reference',
        'amount',
        'transaction_status',
        'paystack_access_code',
        'paystack_authorization_url',
        'paid_at',
        'verified_at',
        'payload',
        'period_starts_at',
        'period_ends_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'verified_at' => 'datetime',
        'payload' => 'array',
        'period_starts_at' => 'datetime',
        'period_ends_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public static function generateReference(): string
    {
        return 'HOWSEY-RENEW-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6));
    }
}
