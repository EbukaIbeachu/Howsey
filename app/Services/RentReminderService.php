<?php

namespace App\Services;

use App\Models\Message;
use App\Models\Rent;
use App\Models\RentReminder;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RentReminderService
{
    /**
     * Ordered list of reminder milestones in sequence (weight 1 = earliest).
     * Shared by the scheduler, the exemption start-level filter, and the UI dropdown.
     */
    public static function milestones(): array
    {
        return [
            ['key' => '60_days_before', 'label' => 'Initial renewal reminder (60 days before due)', 'weight' => 1, 'phase' => 'pre'],
            ['key' => '30_days_before', 'label' => 'Second reminder (30 days before due)', 'weight' => 2, 'phase' => 'pre'],
            ['key' => '14_days_before', 'label' => 'Third reminder (14 days before due)', 'weight' => 3, 'phase' => 'pre'],
            ['key' => '7_days_before',  'label' => 'Final courtesy reminder (7 days before due)', 'weight' => 4, 'phase' => 'pre'],
            ['key' => 'due_date',       'label' => 'Payment due notification (due date)', 'weight' => 5, 'phase' => 'due'],
            ['key' => '7_days_after',   'label' => 'First overdue reminder (7 days overdue)', 'weight' => 6, 'phase' => 'post'],
            ['key' => '14_days_after',  'label' => 'Second overdue reminder (14 days overdue)', 'weight' => 7, 'phase' => 'post'],
            ['key' => '21_days_after',  'label' => 'Formal follow-up (21 days overdue)', 'weight' => 8, 'phase' => 'post'],
            ['key' => '30_days_after',  'label' => 'Review & next steps — Notice to Quit (30 days overdue)', 'weight' => 9, 'phase' => 'post'],
        ];
    }

    public static function milestoneByKey(string $key): ?array
    {
        foreach (self::milestones() as $m) {
            if ($m['key'] === $key) {
                return $m;
            }
        }
        return null;
    }

    public function run(Carbon $today = null): array
    {
        $today = ($today ?? now())->copy()->startOfDay();

        $stats = [
            'rents_checked' => 0,
            'milestones_triggered' => 0,
            'messages_sent' => 0,
            'skipped' => 0,
        ];

        $rents = Rent::active()
            ->with(['tenant.user', 'unit.building.manager', 'company'])
            ->whereNotNull('start_date')
            ->get();

        foreach ($rents as $rent) {
            $stats['rents_checked']++;

            try {
                $sent = $this->processRent($rent, $today);
                $stats['milestones_triggered'] += $sent['milestones'];
                $stats['messages_sent'] += $sent['messages'];
            } catch (\Throwable $e) {
                $stats['skipped']++;
                Log::error('Rent reminder processing failed for rent #'.$rent->id, [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $stats;
    }

    private function processRent(Rent $rent, Carbon $today): array
    {
        $dueDate = $rent->next_due_date;

        if (! $dueDate || ! $rent->tenant) {
            return ['milestones' => 0, 'messages' => 0];
        }

        // Exemption: tenant (and their manager/admin copies) are paused entirely.
        if ($rent->tenant->rent_reminders_exempt) {
            return ['milestones' => 0, 'messages' => 0];
        }

        $deltas = $this->applicableDeltas($rent);
        $dueDateDay = $dueDate->copy()->startOfDay();
        $daysDelta = (int) $dueDateDay->diffInDays($today, false); // +ve = overdue, -ve = before due

        if (! in_array($daysDelta, $deltas, true)) {
            return ['milestones' => 0, 'messages' => 0];
        }

        $milestone = $this->milestoneKey($daysDelta);

        // Start level: skip any milestone that comes before the configured starting point.
        $startMilestone = $rent->tenant->rent_reminders_start_milestone;
        if ($startMilestone) {
            $start = self::milestoneByKey($startMilestone);
            $current = self::milestoneByKey($milestone);
            if ($start && $current && $current['weight'] < $start['weight']) {
                return ['milestones' => 0, 'messages' => 0];
            }
        }

        $dueDateString = $dueDateDay->toDateString();

        // Idempotency: never send the same milestone twice for the same due date.
        if (RentReminder::where('rent_id', $rent->id)
            ->whereDate('due_date', $dueDateString)
            ->where('milestone', $milestone)
            ->exists()
        ) {
            return ['milestones' => 0, 'messages' => 0];
        }

        $sender = $this->resolveSender($rent);
        if (! $sender) {
            Log::warning('No admin/manager sender available for rent reminder', ['rent_id' => $rent->id]);
            return ['milestones' => 0, 'messages' => 0];
        }

        $recipients = $this->resolveRecipients($rent);
        if (empty($recipients)) {
            return ['milestones' => 0, 'messages' => 0];
        }

        $sentTo = [];
        $messagesSent = 0;

        DB::transaction(function () use ($rent, $milestone, $dueDateDay, $dueDateString, $sender, $recipients, $daysDelta, &$sentTo, &$messagesSent) {
            foreach ($recipients as $recipient) {
                $body = $this->buildBody($recipient['type'], $rent, $daysDelta, $dueDateString);

                $message = Message::create([
                    'sender_id' => $sender->id,
                    'recipient_id' => $recipient['user']->id,
                    'body' => $body,
                ]);

                $recipient['user']->notify(new NewMessageNotification(
                    $sender->name ?? 'System',
                    $body,
                    $message->id
                ));

                $sentTo[] = $recipient['user']->id;
                $messagesSent++;
            }

            RentReminder::create([
                'rent_id' => $rent->id,
                'company_id' => $rent->company_id,
                'due_date' => $dueDateDay,
                'milestone' => $milestone,
                'sent_to' => array_values(array_unique($sentTo)),
                'sent_at' => now(),
            ]);
        });

        return ['milestones' => 1, 'messages' => $messagesSent];
    }

    private function applicableDeltas(Rent $rent): array
    {
        $post = [7, 14, 21, 30];
        $pre = $rent->rent_duration_value === Rent::DURATION_MONTHLY
            ? [-7]
            : [-60, -30, -14, -7];

        return array_merge($pre, [0], $post);
    }

    private function milestoneKey(int $delta): string
    {
        if ($delta === 0) {
            return 'due_date';
        }
        return $delta < 0 ? abs($delta).'_days_before' : $delta.'_days_after';
    }

    private function resolveSender(Rent $rent): ?User
    {
        $admin = User::where('company_id', $rent->company_id)
            ->whereHas('role', function ($q) {
                $q->whereIn(DB::raw('LOWER(name)'), ['admin', 'super_admin']);
            })
            ->first();

        if ($admin) {
            return $admin;
        }

        // Fallback: the building manager.
        return optional(optional($rent->unit)->building)->manager;
    }

    private function resolveRecipients(Rent $rent): array
    {
        $recipients = [];

        if ($rent->tenant && $rent->tenant->user) {
            $recipients[] = ['type' => 'tenant', 'user' => $rent->tenant->user];
        }

        $manager = optional(optional($rent->unit)->building)->manager;
        if ($manager) {
            $recipients[] = ['type' => 'manager', 'user' => $manager];
        }

        $admins = User::where('company_id', $rent->company_id)
            ->whereHas('role', function ($q) {
                $q->whereIn(DB::raw('LOWER(name)'), ['admin', 'super_admin']);
            })
            ->get();

        foreach ($admins as $admin) {
            // Avoid duplicating the manager if they are also the admin sender.
            if ($manager && $admin->id === $manager->id) {
                continue;
            }
            $recipients[] = ['type' => 'admin', 'user' => $admin];
        }

        return $recipients;
    }

    private function buildBody(string $recipientType, Rent $rent, int $delta, string $dueDateString): string
    {
        $tenantName = $rent->tenant->full_name ?? 'the tenant';
        $unitLabel = optional($rent->unit)->full_address ?? 'the assigned unit';
        $amount = number_format((float) $rent->annual_amount, 2);
        $duration = $rent->rent_duration_label;
        $dueLabel = Carbon::parse($dueDateString)->format('jS F, Y');

        $isNoticeToQuit = $delta === 30;

        if ($recipientType === 'tenant') {
            if ($isNoticeToQuit) {
                return "NOTICE TO QUIT\n\n"
                    ."Dear {$tenantName},\n\n"
                    ."Re: Notice to Quit for Breach of Tenancy Agreement\n\n"
                    ."Pursuant to your tenancy agreement for {$unitLabel}, you are hereby served a NOTICE TO QUIT "
                    ."for breach of contract, specifically the non-payment of rent.\n\n"
                    ."Your {$duration} rent of ₦{$amount}, due on {$dueLabel}, has been outstanding for 30 days. "
                    ."Despite previous reminders, payment has not been received.\n\n"
                    ."You are required to settle all outstanding rent or vacate the premises within seven (7) days "
                    ."from the date of this notice. Failure to comply will result in further action, including "
                    ."legal proceedings for possession and recovery of arrears.\n\n"
                    ."Contact your property manager immediately to resolve this matter.\n\n"
                    ."— Management";
            }

            return match ($delta) {
                -60 => "Initial rent renewal reminder:\n\nDear {$tenantName}, your {$duration} rent of ₦{$amount} for {$unitLabel} is due on {$dueLabel} (60 days from now). Please plan your renewal payment in advance to avoid any disruption.",
                -30 => "Second rent renewal reminder:\n\nDear {$tenantName}, your {$duration} rent of ₦{$amount} for {$unitLabel} is due on {$dueLabel} (30 days from now). Kindly make arrangements to pay on or before the due date.",
                -14 => "Third rent renewal reminder:\n\nDear {$tenantName}, your {$duration} rent of ₦{$amount} for {$unitLabel} is due on {$dueLabel} (14 days from now). Please process your payment soon.",
                -7 => "Final courtesy reminder:\n\nDear {$tenantName}, your {$duration} rent of ₦{$amount} for {$unitLabel} is due on {$dueLabel} (7 days from now). This is your final reminder before the due date.",
                0 => "Payment due notification:\n\nDear {$tenantName}, your {$duration} rent of ₦{$amount} for {$unitLabel} is due today ({$dueLabel}). Please make your payment today to avoid late charges.",
                7 => "First overdue reminder:\n\nDear {$tenantName}, your {$duration} rent of ₦{$amount} for {$unitLabel}, due on {$dueLabel}, is now 7 days overdue. Please settle the outstanding amount immediately.",
                14 => "Second overdue reminder:\n\nDear {$tenantName}, your {$duration} rent of ₦{$amount} for {$unitLabel}, due on {$dueLabel}, is now 14 days overdue. Prompt payment is required to avoid further action.",
                21 => "Formal follow-up:\n\nDear {$tenantName}, your {$duration} rent of ₦{$amount} for {$unitLabel}, due on {$dueLabel}, is now 21 days overdue. This is a formal demand for payment. Please settle without delay.",
                default => '',
            };
        }

        // Manager / Admin informational copy.
        $milestoneLabel = match ($delta) {
            -60 => 'Initial renewal reminder (60 days before due)',
            -30 => 'Second renewal reminder (30 days before due)',
            -14 => 'Third renewal reminder (14 days before due)',
            -7 => 'Final courtesy reminder (7 days before due)',
            0 => 'Payment due notification (due date)',
            7 => 'First overdue reminder (7 days overdue)',
            14 => 'Second overdue reminder (14 days overdue)',
            21 => 'Formal follow-up (21 days overdue)',
            30 => 'Review and next steps — Notice to Quit issued (30 days overdue)',
            default => $delta.' days',
        };

        $recipientLabel = $recipientType === 'manager' ? 'Manager' : 'Admin';

        $body = "Automated rent renewal update ({$recipientLabel} copy)\n\n"
            ."Tenant: {$tenantName}\n"
            ."Unit: {$unitLabel}\n"
            ."Rent: ₦{$amount} ({$duration})\n"
            ."Due date: {$dueLabel}\n"
            ."Milestone: {$milestoneLabel}\n\n"
            ."The tenant has been sent the corresponding reminder automatically.";

        if ($isNoticeToQuit) {
            $body .= "\n\nACTION REQUIRED: A Notice to Quit has been issued to this tenant for non-payment of rent. "
                ."Please follow up and take the appropriate next steps.";
        }

        return $body;
    }
}
