<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'logo_path',
        'primary_color',
        'email_header',
        'receipt_header',
        'address',
        'support_email',
        'support_phone',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
