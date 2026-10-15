<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'type',
        'subject',
        'body_html',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
