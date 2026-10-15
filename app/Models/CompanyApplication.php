<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CompanyApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'logo_path',
        'primary_color',
        'address',
        'support_email',
        'support_phone',
        'receipt_header',
        'email_header',
        'admin_name',
        'admin_email',
        'admin_phone',
        'admin_password',
        'sample_dataset',
        'payment_reference',
        'payment_amount',
        'paystack_access_code',
        'paystack_authorization_url',
        'paystack_transaction_status',
        'paystack_paid_at',
        'paystack_verified_at',
        'paystack_payload',
        'application_status',
        'submitted_at',
        'reviewed_at',
        'approved_at',
        'approved_by',
        'review_notes',
        'company_id',
    ];

    protected $casts = [
        'sample_dataset' => 'boolean',
        'payment_amount' => 'decimal:2',
        'paystack_paid_at' => 'datetime',
        'paystack_verified_at' => 'datetime',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'paystack_payload' => 'array',
    ];

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public static function generatePaymentReference(): string
    {
        return 'HOWSEY-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6));
    }
}
