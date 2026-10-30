<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\RecordCreator;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use BelongsToCompany, HasFactory, RecordCreator;

    protected $fillable = [
        'company_id',
        'rent_id',
        'payment_date',
        'due_date',
        'amount',
        'payment_method',
        'status',
        'reference',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'due_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function rent()
    {
        return $this->belongsTo(Rent::class);
    }

    public function tenant()
    {
        return $this->hasOneThrough(Tenant::class, Rent::class);
    }

    /**
     * Record a real payment for a rent, absorbing the PENDING placeholder that
     * the unit onboarding workflow created for the same rental period.
     *
     * Updating the placeholder in place (instead of inserting a second row)
     * stops the building financials from listing a duplicate PENDING and
     * COMPLETED pair for a single payment.
     */
    public static function recordAgainst(?Rent $rent, array $attributes): self
    {
        if ($rent) {
            $attributes['rent_id'] = $rent->id;
        }

        $status = strtoupper((string) ($attributes['status'] ?? '')) ?: 'COMPLETED';
        $attributes['status'] = $status;

        if ($rent && $status === 'COMPLETED') {
            $placeholder = self::outstandingPlaceholderFor($rent, $attributes['due_date'] ?? null);

            if ($placeholder) {
                $placeholder->fill($attributes)->save();

                return $placeholder;
            }
        }

        return self::create($attributes);
    }

    /**
     * Finds the rent's outstanding PENDING row for the given period. The
     * onboarding placeholder stores the expected payment date as its due date,
     * so match on that first; otherwise fall back to the rent's only pending
     * row when there is exactly one.
     */
    private static function outstandingPlaceholderFor(Rent $rent, ?string $dueDate): ?self
    {
        $pending = self::query()
            ->where('rent_id', $rent->id)
            ->where('status', 'PENDING')
            ->get();

        if ($pending->isEmpty()) {
            return null;
        }

        if ($dueDate) {
            $target = Carbon::parse($dueDate)->toDateString();

            $match = $pending->first(function (self $payment) use ($target) {
                return $payment->due_date && $payment->due_date->toDateString() === $target;
            });

            if ($match) {
                return $match;
            }
        }

        return $pending->count() === 1 ? $pending->first() : null;
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year);
    }

    public function scopeLastMonth($query)
    {
        return $query->whereMonth('payment_date', now()->subMonth()->month)
            ->whereYear('payment_date', now()->subMonth()->year);
    }
}
