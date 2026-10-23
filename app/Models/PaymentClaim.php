<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentClaim extends Model
{
    use BelongsToCompany, HasFactory;

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_CONFIRMED = 'CONFIRMED';
    public const STATUS_REJECTED = 'REJECTED';

    protected $fillable = [
        'company_id',
        'rent_id',
        'tenant_id',
        'submitted_by',
        'amount',
        'payment_date',
        'payment_method',
        'reference',
        'proof_path',
        'note',
        'status',
        'confirmed_by',
        'confirmed_at',
        'review_note',
        'payment_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'confirmed_at' => 'datetime',
    ];

    public function rent()
    {
        return $this->belongsTo(Rent::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function confirmer()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function getIsPendingAttribute(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
