<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'email_enabled',
        'send_overdue_rent_reminders',
        'send_lease_expiry_reminders',
        'lease_expiry_days_notice',
        'from_name',
        'from_email',
    ];

    protected $casts = [
        'email_enabled' => 'boolean',
        'send_overdue_rent_reminders' => 'boolean',
        'send_lease_expiry_reminders' => 'boolean',
        'lease_expiry_days_notice' => 'integer',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
