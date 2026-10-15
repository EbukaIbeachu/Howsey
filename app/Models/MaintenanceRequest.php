<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\RecordCreator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceRequest extends Model
{
    use BelongsToCompany, HasFactory, RecordCreator;

    protected $fillable = [
        'company_id',
        'unit_id',
        'tenant_id',
        'title',
        'description',
        'type',
        'priority',
        'status',
        'resolved_at',
        'created_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
