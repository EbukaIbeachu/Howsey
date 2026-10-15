<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'type',
        'channel',
        'subject',
        'body',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
