<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\RecordCreator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use BelongsToCompany, HasFactory, RecordCreator;

    protected $fillable = [
        'company_id',
        'user_id',
        'unit_id',
        'full_name',
        'phone',
        'email',
        'profile_image',
        'room_number',
        'move_in_date',
        'move_out_date',
        'active',
        'rent_reminders_exempt',
        'rent_reminders_start_milestone',
        'created_by',
    ];

    protected $casts = [
        'move_in_date' => 'date',
        'move_out_date' => 'date',
        'active' => 'boolean',
        'rent_reminders_exempt' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function rents()
    {
        return $this->hasMany(Rent::class);
    }

    public function currentRent()
    {
        return $this->hasOne(Rent::class)->where('status', 'ACTIVE')->latest();
    }

    public function payments()
    {
        return $this->hasManyThrough(Payment::class, Rent::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
