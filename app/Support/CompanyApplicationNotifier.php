<?php

namespace App\Support;

use App\Models\CompanyApplication;
use App\Models\User;
use App\Notifications\RoleRequestNotification;
use Illuminate\Support\Facades\Schema;

class CompanyApplicationNotifier
{
    public static function notifySuperAdmins(CompanyApplication $application, string $title, string $message): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        User::query()
            ->whereHas('role', function ($query) {
                $query->whereRaw('LOWER(name) = ?', ['super_admin']);
            })
            ->get()
            ->each(function (User $user) use ($application, $title, $message): void {
                $user->notify(new RoleRequestNotification([
                    'title' => $title,
                    'message' => $message,
                    'url' => route('company-applications.show', $application),
                    'request_type' => 'company_application',
                    'subject_id' => $application->id,
                ]));
            });
    }
}
