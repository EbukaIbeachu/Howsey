<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\RecordCreator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use BelongsToCompany, HasFactory, RecordCreator;

    protected $fillable = [
        'company_id',
        'building_id',
        'unit_number',
        'floor',
        'bedrooms',
        'bathrooms',
        'status',
        'created_by',
    ];

    protected $casts = [
        'bedrooms' => 'integer',
        'bathrooms' => 'integer',
    ];

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function tenants()
    {
        return $this->hasMany(Tenant::class);
    }

    public function currentTenant()
    {
        return $this->hasOne(Tenant::class)->where('active', true);
    }

    public function rents()
    {
        return $this->hasMany(Rent::class);
    }

    public function getFullAddressAttribute(): string
    {
        return "{$this->unit_number}".($this->floor ? " - Floor {$this->floor}" : '').", {$this->building->name}";
    }
}
