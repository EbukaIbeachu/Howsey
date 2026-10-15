<?php

namespace App\Models;

use App\Traits\RecordCreator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @deprecated This model is part of the legacy expense mechanism.
 * Please use the new Accounting module for any expense-related operations.
 */
class LegacyExpense extends Model
{
    use HasFactory, RecordCreator;

    protected $table = 'legacy_expenses';

    protected $fillable = []; // Prevent mass assignment on legacy table

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
    ];

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    /**
     * Prevent any updates or deletions on the legacy table.
     */
    public static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            return false;
        });

        static::deleting(function ($model) {
            return false;
        });
    }
}
