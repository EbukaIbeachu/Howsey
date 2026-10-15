<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EditRequest extends Model
{
    use BelongsToCompany, HasFactory;

    public const DEFAULT_DURATION_VALUE = 2;

    public const DEFAULT_DURATION_UNIT = 'hours';

    protected $fillable = [
        'company_id',
        'accounting_entry_id',
        'user_id',
        'reason',
        'status',
        'admin_id',
        'approved_at',
        'expires_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function accountingEntry()
    {
        return $this->belongsTo(AccountingEntry::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public static function expireStaleApprovals(): void
    {
        static::query()
            ->where('status', 'approved')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update([
                'status' => 'timed_out',
                'updated_at' => now(),
            ]);
    }
}
