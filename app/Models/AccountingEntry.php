<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountingEntry extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id',
        'building_id',
        'type',
        'category',
        'description',
        'amount',
        'entry_date',
        'is_locked',
        'finalized_at',
        'created_by',
        'updated_by',
        'status',
        'extra_details',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'entry_date' => 'date',
        'is_locked' => 'boolean',
        'finalized_at' => 'datetime',
        'extra_details' => 'array',
    ];

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function editRequests()
    {
        return $this->hasMany(EditRequest::class);
    }

    /**
     * Scope for income entries
     */
    public function scopeIncome($query)
    {
        return $query->where('type', 'income');
    }

    /**
     * Scope for expense entries
     */
    public function scopeExpense($query)
    {
        return $query->where('type', 'expense');
    }

    public function scopeNotRejected($query)
    {
        return $query->where('status', '!=', 'rejected');
    }

    /**
     * Check if entry is locked
     */
    public function isLocked(): bool
    {
        return (bool) $this->is_locked;
    }

    /**
     * Check if user has active approved edit request
     */
    public function hasActiveEditPermission($userId): bool
    {
        EditRequest::expireStaleApprovals();

        return $this->editRequests()
            ->where('user_id', $userId)
            ->where('status', 'approved')
            ->where('expires_at', '>', now())
            ->exists();
    }

    public function canDirectlyEditByAccountant($userId): bool
    {
        $userId = (int) $userId;

        return $this->type === 'expense'
            && ! $this->isLocked()
            && $this->status === 'pending'
            && in_array($userId, [(int) $this->created_by, (int) $this->updated_by], true);
    }

    public function isInAccountantWorkflow(): bool
    {
        $creator = $this->creator;
        $updater = $this->updater;

        return $this->type === 'expense'
            && (($creator && $creator->isAccountant()) || ($updater && $updater->isAccountant()));
    }
}
