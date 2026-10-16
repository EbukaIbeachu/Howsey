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

    /**
     * Derived unit type. A unit configured with no bedrooms and no bathrooms
     * is treated as non-residential (warehouse); everything else is residential.
     */
    public function getUnitTypeAttribute(): string
    {
        return ($this->bedrooms === 0 && $this->bathrooms === 0) ? 'WAREHOUSE' : 'RESIDENTIAL';
    }

    public function getUnitTypeLabelAttribute(): string
    {
        return $this->unit_type === 'WAREHOUSE' ? 'Warehouse' : 'Residential';
    }

    /**
     * Wording used by the tenancy agreement's permitted-use covenant.
     */
    public function getPermittedUseClauseAttribute(): string
    {
        return $this->unit_type === 'WAREHOUSE'
            ? 'storage, warehousing, and ancillary commercial purposes'
            : 'residential purposes only';
    }
}
