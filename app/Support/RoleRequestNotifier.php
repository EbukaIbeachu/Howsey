<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\RoleRequestNotification;
use App\Support\WebPushSender;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RoleRequestNotifier
{
    public static function available(): bool
    {
        return Schema::hasTable('notifications');
    }

    public static function notifyAdmins(array $payload, ?int $excludeUserId = null): void
    {
        if (! self::available()) {
            return;
        }

        $companyId = optional(auth()->user())->company_id;

        self::notifyUsers(
            User::query()
                ->whereHas('role', function ($query) {
                    $query->whereRaw('LOWER(name) = ?', ['admin']);
                })
                ->when($companyId, function ($query) use ($companyId) {
                    $query->where('company_id', $companyId);
                })
                ->get(),
            $payload,
            $excludeUserId
        );
    }

    public static function notifyAdminsAndManager(?User $manager, array $payload, ?int $excludeUserId = null): void
    {
        if (! self::available()) {
            return;
        }

        $recipients = User::query()
            ->whereHas('role', function ($query) {
                $query->whereRaw('LOWER(name) = ?', ['admin']);
            })
            ->when(optional(auth()->user())->company_id, function ($query, $companyId) {
                $query->where('company_id', $companyId);
            })
            ->get();

        if ($manager) {
            $recipients->push($manager);
        }

        self::notifyUsers($recipients, $payload, $excludeUserId);
    }

    public static function notifyUser(?User $user, array $payload, ?int $excludeUserId = null): void
    {
        if (! self::available() || ! $user instanceof User) {
            return;
        }

        self::notifyUsers(collect([$user]), $payload, $excludeUserId);
    }

    public static function notifyUsers(Collection $users, array $payload, ?int $excludeUserId = null): void
    {
        $recipients = $users
            ->filter(function ($user) use ($excludeUserId) {
                return $user instanceof User && $user->id !== $excludeUserId;
            })
            ->unique('id');

        $recipients->each(function (User $user) use ($payload) {
            $user->notify(new RoleRequestNotification($payload));
        });

        if ($recipients->isEmpty()) {
            return;
        }

        // Push delivery should not hold up the user's current action.
        app()->terminating(function () use ($recipients) {
            $recipients->each(function (User $user) {
                WebPushSender::sendToUser($user);
            });
        });
    }
}
