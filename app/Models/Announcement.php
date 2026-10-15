<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id',
        'building_id',
        'manager_id',
        'title',
        'content',
    ];

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function dismissedBy()
    {
        return $this->belongsToMany(User::class, 'announcement_dismissals', 'announcement_id', 'user_id')->withTimestamps();
    }

    public function viewedBy()
    {
        return $this->belongsToMany(User::class, 'announcement_views', 'announcement_id', 'user_id')->withTimestamps();
    }
}
