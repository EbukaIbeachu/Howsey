<?php

namespace Tests\Feature;

use App\Models\Rent;
use Carbon\Carbon;
use Tests\TestCase;

class RentDurationSupportTest extends TestCase
{
    public function test_quarterly_duration_exposes_expected_labels_schedule_and_notice(): void
    {
        $rent = new Rent([
            'annual_amount' => 450000,
            'rent_duration' => Rent::DURATION_QUARTERLY,
            'start_date' => '2026-01-13',
            'end_date' => '2026-10-12',
        ]);

        $this->assertSame('Quarterly', $rent->rent_duration_label);
        $this->assertSame('Quarterly Rent', $rent->rent_amount_label);
        $this->assertSame('/quarter', $rent->rent_interval_suffix);
        $this->assertSame('per quarter', $rent->agreement_rent_interval_text);
        $this->assertSame('Three (3) Months', $rent->notice_to_quit_text);
        $this->assertSame('Every 3 months on the 13th', $rent->due_schedule_label);
    }

    public function test_notice_to_quit_text_matches_supported_tenancy_types(): void
    {
        $this->assertSame('One (1) Week', (new Rent(['rent_duration' => Rent::DURATION_WEEKLY]))->notice_to_quit_text);
        $this->assertSame('One (1) Month', (new Rent(['rent_duration' => Rent::DURATION_MONTHLY]))->notice_to_quit_text);
        $this->assertSame('Three (3) Months', (new Rent(['rent_duration' => Rent::DURATION_QUARTERLY]))->notice_to_quit_text);
        $this->assertSame('Six (6) Months', (new Rent(['rent_duration' => Rent::DURATION_ANNUALLY]))->notice_to_quit_text);
    }

    public function test_quarterly_advance_cycle_moves_forward_by_three_months(): void
    {
        $rent = new Rent([
            'rent_duration' => Rent::DURATION_QUARTERLY,
        ]);

        $nextDate = $rent->advanceCycle(Carbon::parse('2026-01-31'));

        $this->assertSame('2026-04-30', $nextDate->format('Y-m-d'));
    }
}
