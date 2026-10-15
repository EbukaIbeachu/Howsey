<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class ManagerEditRequest extends Model
{
    use BelongsToCompany, HasFactory;

    public const EDIT_WINDOW_HOURS = 2;
    public const REQUEST_KIND_EDIT = 'edit';
    public const REQUEST_KIND_AGREEMENT_DOWNLOAD = 'agreement_download';
    public const REQUEST_KIND_AGREEMENT_DELETE = 'agreement_delete';

    public const REQUEST_KINDS = [
        self::REQUEST_KIND_EDIT,
        self::REQUEST_KIND_AGREEMENT_DOWNLOAD,
        self::REQUEST_KIND_AGREEMENT_DELETE,
    ];

    protected $fillable = [
        'company_id',
        'user_id',
        'requestable_type',
        'requestable_id',
        'request_kind',
        'reason',
        'status',
        'admin_id',
        'admin_notes',
        'reviewed_at',
        'expires_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected static bool $requestKindColumnChecked = false;
    protected static bool $requestKindColumnAvailable = false;

    public function requester()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function requestable()
    {
        return $this->morphTo();
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeActiveApproval($query)
    {
        return $query
            ->where('status', 'approved')
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', now());
    }

    public function hasActiveEditWindow(): bool
    {
        return $this->status === 'approved'
            && $this->expires_at !== null
            && $this->expires_at->isFuture();
    }

    public function getRequestKindLabelAttribute(): string
    {
        return self::requestKindLabel($this->request_kind);
    }

    public static function expireStaleApprovals(?int $userId = null, ?string $requestableType = null, ?int $requestableId = null): int
    {
        $query = static::query()
            ->where('status', 'approved')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        if ($requestableType !== null) {
            $query->where('requestable_type', $requestableType);
        }

        if ($requestableId !== null) {
            $query->where('requestable_id', $requestableId);
        }

        return $query->update([
            'status' => 'timed_out',
            'updated_at' => now(),
        ]);
    }

    public static function latestForRecord(int $userId, string $requestableType, int $requestableId, string $requestKind = self::REQUEST_KIND_EDIT): ?self
    {
        static::expireStaleApprovals($userId, $requestableType, $requestableId);

        $query = static::query()
            ->where('user_id', $userId)
            ->where('requestable_type', $requestableType)
            ->where('requestable_id', $requestableId)
            ->latest('id');

        if (static::supportsRequestKinds()) {
            $query->where('request_kind', $requestKind);
        } elseif ($requestKind !== self::REQUEST_KIND_EDIT) {
            return null;
        }

        return $query->first();
    }

    public static function hasActivePermissionFor(int $userId, Model $requestable, string $requestKind = self::REQUEST_KIND_EDIT): bool
    {
        $requestableType = $requestable::class;
        $requestableId = (int) $requestable->getKey();

        static::expireStaleApprovals($userId, $requestableType, $requestableId);

        $query = static::query()
            ->where('user_id', $userId)
            ->where('requestable_type', $requestableType)
            ->where('requestable_id', $requestableId)
            ->activeApproval();

        if (static::supportsRequestKinds()) {
            $query->where('request_kind', $requestKind);
        } elseif ($requestKind !== self::REQUEST_KIND_EDIT) {
            return false;
        }

        return $query->exists();
    }

    public static function denialMessageFor(int $userId, Model $requestable, string $requestKind = self::REQUEST_KIND_EDIT): string
    {
        if (! static::supportsRequestKinds() && $requestKind !== self::REQUEST_KIND_EDIT) {
            return 'This agreement permission flow will work after the latest request migration is installed.';
        }

        $latestRequest = static::latestForRecord($userId, $requestable::class, (int) $requestable->getKey(), $requestKind);
        $baseMessage = match ($requestKind) {
            self::REQUEST_KIND_AGREEMENT_DOWNLOAD => 'Managers cannot download this uploaded agreement directly. Request admin download approval instead.',
            self::REQUEST_KIND_AGREEMENT_DELETE => 'Managers cannot delete this uploaded agreement directly. Request admin delete approval instead.',
            default => 'Managers cannot edit this record directly. Request admin edit instead.',
        };

        return match ($latestRequest?->status) {
            'pending' => 'Your '.strtolower(self::requestKindLabel($requestKind)).' request is still pending admin review.',
            'timed_out' => 'Your approved '.strtolower(self::requestKindLabel($requestKind)).' window has timed out. Submit another request.',
            'rejected' => 'Your last '.strtolower(self::requestKindLabel($requestKind)).' request was rejected. Submit another request if further action is needed.',
            default => $baseMessage,
        };
    }

    public static function requestKindLabel(?string $requestKind): string
    {
        return match ($requestKind) {
            self::REQUEST_KIND_AGREEMENT_DOWNLOAD => 'Agreement Download',
            self::REQUEST_KIND_AGREEMENT_DELETE => 'Agreement Delete',
            default => 'Edit',
        };
    }

    public static function supportsRequestKinds(): bool
    {
        if (! static::$requestKindColumnChecked) {
            static::$requestKindColumnAvailable = Schema::hasColumn('manager_edit_requests', 'request_kind');
            static::$requestKindColumnChecked = true;
        }

        return static::$requestKindColumnAvailable;
    }
}
