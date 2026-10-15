<?php

namespace App\Console\Commands;

use App\Services\RentReminderService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendRentReminders extends Command
{
    protected $signature = 'rent:send-reminders {--date= : The date to process (Y-m-d). Defaults to today.}';

    protected $description = 'Send automated rent renewal / overdue reminders to tenants, their managers, and company admins.';

    public function handle(RentReminderService $service): int
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))->startOfDay()
            : now()->startOfDay();

        $this->info("Processing rent reminders for {$date->toDateString()}...");

        $stats = $service->run($date);

        $this->info('Rents checked: '.$stats['rents_checked']);
        $this->info('Milestones triggered: '.$stats['milestones_triggered']);
        $this->info('Messages sent: '.$stats['messages_sent']);
        $this->info('Skipped (errors): '.$stats['skipped']);

        return self::SUCCESS;
    }
}
