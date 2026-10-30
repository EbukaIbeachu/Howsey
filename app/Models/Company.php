<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'status',
        'platform_status',
        'platform_status_note',
        'platform_status_changed_at',
        'platform_status_changed_by',
        'is_demo',
        'onboarded_at',
        'subscription_started_at',
        'subscription_expires_at',
        'subscription_fee',
    ];

    protected $casts = [
        'is_demo' => 'boolean',
        'onboarded_at' => 'datetime',
        'platform_status_changed_at' => 'datetime',
        'subscription_started_at' => 'datetime',
        'subscription_expires_at' => 'datetime',
        'subscription_fee' => 'decimal:2',
    ];

    public function settings()
    {
        return $this->hasOne(CompanySetting::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function buildings()
    {
        return $this->hasMany(Building::class);
    }

    public function notificationSettings()
    {
        return $this->hasOne(NotificationSetting::class);
    }

    public function notificationTemplates()
    {
        return $this->hasMany(NotificationTemplate::class);
    }

    public function documentTemplates()
    {
        return $this->hasMany(DocumentTemplate::class);
    }

    public function subscriptionPayments()
    {
        return $this->hasMany(CompanySubscriptionPayment::class);
    }

    public function platformStatusChangedBy()
    {
        return $this->belongsTo(User::class, 'platform_status_changed_by');
    }

    public function hasExpiredSubscription(): bool
    {
        // HOWSEY access is free, so no workspace is ever treated as expired.
        // The subscription columns are kept for reference only.
        return false;
    }

    public function hasActiveSubscription(): bool
    {
        return ! $this->hasExpiredSubscription();
    }

    public function isInactive(): bool
    {
        return strtolower((string) $this->status) === 'inactive';
    }

    public function isActive(): bool
    {
        return ! $this->isInactive();
    }

    public function isAdminInactive(): bool
    {
        return strtolower((string) $this->status) === 'inactive';
    }

    public function isPlatformInactive(): bool
    {
        return strtolower((string) ($this->platform_status ?: 'active')) === 'inactive';
    }

    public function isAccessibleByCompanyAdmin(): bool
    {
        return ! $this->isPlatformInactive();
    }

    public function adminStatusLabel(): string
    {
        return $this->isAdminInactive() ? 'Inactive' : 'Active';
    }

    public function platformStatusLabel(): string
    {
        return $this->isPlatformInactive() ? 'Inactive' : 'Active';
    }

    public function platformStatusNoteText(): ?string
    {
        $note = trim((string) $this->platform_status_note);

        return $note !== '' ? $note : null;
    }

    public function platformLockMessage(): string
    {
        $message = 'This workspace has been deactivated by the HOWSEY platform owner. Contact the platform team for reactivation.';

        if ($this->platformStatusNoteText()) {
            $message .= ' Note: '.$this->platformStatusNoteText();
        }

        return $message;
    }

    public function adminLockMessage(): string
    {
        return 'This workspace is currently deactivated. Contact a company admin to reactivate it.';
    }

    public function subscriptionFeeAmount(): float
    {
        return (float) ($this->subscription_fee ?: config('services.paystack.company_subscription_fee', 50000));
    }

    public function nextRenewalStartAt(): Carbon
    {
        return $this->subscription_expires_at !== null && $this->subscription_expires_at->isFuture()
            ? $this->subscription_expires_at->copy()
            : now();
    }
}
