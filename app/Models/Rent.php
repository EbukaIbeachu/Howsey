<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\RecordCreator;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Rent extends Model
{
    use BelongsToCompany, HasFactory, RecordCreator;

    public const DURATION_DAILY = 'daily';
    public const DURATION_WEEKLY = 'weekly';
    public const DURATION_MONTHLY = 'monthly';
    public const DURATION_ANNUALLY = 'annually';

    public const DURATIONS = [
        self::DURATION_DAILY,
        self::DURATION_WEEKLY,
        self::DURATION_MONTHLY,
        self::DURATION_ANNUALLY,
    ];

    protected $fillable = [
        'company_id',
        'tenant_id',
        'unit_id',
        'annual_amount',
        'rent_duration',
        'due_day',
        'start_date',
        'end_date',
        'status',
        'signed_agreement_path',
        'created_by',
    ];

    protected $casts = [
        'annual_amount' => 'decimal:2',
        'due_day' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function completedPayments()
    {
        return $this->hasMany(Payment::class)->where('status', 'COMPLETED');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    public function getTotalPaidAttribute()
    {
        if ($this->relationLoaded('payments')) {
            return $this->payments
                ->filter(function (Payment $payment) {
                    return strtoupper((string) $payment->status) === 'COMPLETED';
                })
                ->sum('amount');
        }

        return $this->completedPayments()->sum('amount');
    }

    public function getRentDurationValueAttribute(): string
    {
        $duration = strtolower((string) ($this->attributes['rent_duration'] ?? self::DURATION_ANNUALLY));

        return in_array($duration, self::DURATIONS, true) ? $duration : self::DURATION_ANNUALLY;
    }

    public function getRentDurationLabelAttribute(): string
    {
        return match ($this->rent_duration_value) {
            self::DURATION_DAILY => 'Daily',
            self::DURATION_WEEKLY => 'Weekly',
            self::DURATION_MONTHLY => 'Monthly',
            default => 'Annually',
        };
    }

    public function getRentAmountLabelAttribute(): string
    {
        return match ($this->rent_duration_value) {
            self::DURATION_DAILY => 'Daily Rent',
            self::DURATION_WEEKLY => 'Weekly Rent',
            self::DURATION_MONTHLY => 'Monthly Rent',
            default => 'Annual Rent',
        };
    }

    public function getRentIntervalSuffixAttribute(): string
    {
        return match ($this->rent_duration_value) {
            self::DURATION_DAILY => '/day',
            self::DURATION_WEEKLY => '/week',
            self::DURATION_MONTHLY => '/month',
            default => '/year',
        };
    }

    public function getDueScheduleLabelAttribute(): string
    {
        if (! $this->start_date) {
            return 'N/A';
        }

        return match ($this->rent_duration_value) {
            self::DURATION_DAILY => 'Every day',
            self::DURATION_WEEKLY => 'Every '.$this->start_date->format('l'),
            self::DURATION_MONTHLY => 'Every month on the '.$this->start_date->format('jS'),
            default => 'Every year on '.$this->start_date->format('jS F'),
        };
    }

    public function getLeasePeriodLabelAttribute(): string
    {
        if (! $this->start_date) {
            return 'N/A';
        }

        $start = $this->start_date->format('Y-m-d');
        $end = $this->end_date ? $this->end_date->format('Y-m-d') : 'Indefinite';

        return $start.' to '.$end;
    }

    public function getLeaseTermDescriptionAttribute(): string
    {
        if (! $this->start_date) {
            return 'an unspecified term';
        }

        if (! $this->end_date) {
            return 'an ongoing term';
        }

        $days = max(1, $this->start_date->diffInDays($this->end_date) + 1);
        $weeks = max(1, (int) ceil($days / 7));
        $months = max(1, (int) ceil($days / 30));
        $years = max(1, $this->start_date->diffInYears($this->end_date));

        return match ($this->rent_duration_value) {
            self::DURATION_DAILY => $days.' day'.($days === 1 ? '' : 's'),
            self::DURATION_WEEKLY => $weeks.' week'.($weeks === 1 ? '' : 's'),
            self::DURATION_MONTHLY => $months.' month'.($months === 1 ? '' : 's'),
            default => $years.' year'.($years === 1 ? '' : 's'),
        };
    }

    public function getBalanceAttribute()
    {
        $start = $this->start_date;
        $end = $this->end_date ? $this->end_date : now();

        // Ensure we don't calculate past today if the lease is still active
        $calculationEnd = $end->isFuture() ? now() : $end;

        if ($start->isFuture()) {
            return -$this->total_paid;
        }

        $cyclesOwed = $this->countDueCyclesBetween($start->copy(), $calculationEnd->copy());
        $expectedTotal = $cyclesOwed * $this->annual_amount;

        return $expectedTotal - $this->total_paid;
    }

    public function getNextDueDateAttribute()
    {
        if ($this->annual_amount <= 0) {
            return null;
        }

        if (! $this->start_date) {
            return null;
        }

        $cyclesPaid = floor($this->total_paid / $this->annual_amount);

        return $this->advanceCycle($this->start_date->copy(), (int) $cyclesPaid);
    }

    public function countDueCyclesBetween(Carbon $periodStart, Carbon $periodEnd): int
    {
        $startDate = $this->start_date;
        if (! $startDate || $periodEnd->lt($periodStart)) {
            return 0;
        }

        $calculationEnd = $periodEnd->copy();
        if ($this->end_date) {
            $calculationEnd = $calculationEnd->min($this->end_date->copy()->endOfDay());
        }

        if ($startDate->gt($calculationEnd)) {
            return 0;
        }

        $count = 0;
        $dueDate = $startDate->copy()->startOfDay();

        while ($dueDate->lte($calculationEnd)) {
            if ($dueDate->betweenIncluded($periodStart, $periodEnd)) {
                $count++;
            }

            $dueDate = $this->advanceCycle($dueDate);
        }

        return $count;
    }

    public function advanceCycle(Carbon $date, int $steps = 1): Carbon
    {
        $nextDate = $date->copy();

        for ($index = 0; $index < $steps; $index++) {
            match ($this->rent_duration_value) {
                self::DURATION_DAILY => $nextDate->addDay(),
                self::DURATION_WEEKLY => $nextDate->addWeek(),
                self::DURATION_MONTHLY => $nextDate->addMonthNoOverflow(),
                default => $nextDate->addYear(),
            };
        }

        return $nextDate;
    }

    public static function rentDurationOptions(): array
    {
        return [
            self::DURATION_DAILY => 'Daily',
            self::DURATION_WEEKLY => 'Weekly',
            self::DURATION_MONTHLY => 'Monthly',
            self::DURATION_ANNUALLY => 'Annually',
        ];
    }

    public static function supportsRentDuration(): bool
    {
        return Schema::hasColumn('rents', 'rent_duration');
    }
}
