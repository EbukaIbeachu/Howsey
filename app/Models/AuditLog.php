<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use BelongsToCompany, HasFactory;

    // Audit logs are immutable, so we only use created_at
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'role',
        'action_type',
        'record_id',
        'record_type',
        'building_id',
        'company_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'device_type',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function building()
    {
        return $this->belongsTo(Building::class);
    }
}
