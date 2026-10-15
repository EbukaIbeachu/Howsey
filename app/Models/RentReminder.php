<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RentReminder extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'rent_id',
        'company_id',
        'due_date',
        'milestone',
        'sent_to',
        'sent_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'sent_to' => 'array',
        'sent_at' => 'datetime',
    ];

    public function rent()
    {
        return $this->belongsTo(Rent::class);
    }
}
