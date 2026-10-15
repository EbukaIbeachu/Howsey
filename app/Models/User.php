<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role_id',
        'company_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function tenant()
    {
        return $this->hasOne(Tenant::class);
    }

    public function pushSubscriptions()
    {
        return $this->hasMany(PushSubscription::class);
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function isAdmin(): bool
    {
        return $this->role && in_array(strtolower((string) $this->role->name), ['admin', 'super_admin'], true);
    }

    public function isManager(): bool
    {
        return $this->role && $this->role->name === 'manager';
    }

    public function isTenant(): bool
    {
        return $this->role && $this->role->name === 'tenant';
    }

    public function isAccountant(): bool
    {
        return $this->role && strtolower($this->role->name) === 'accountant';
    }

    public function isReviewer(): bool
    {
        return $this->role && in_array(strtolower($this->role->name), ['reviewer', 'accountant']);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role && strtolower((string) $this->role->name) === 'super_admin';
    }

    public function permissions()
    {
        return optional($this->role)->permissions() ?? Permission::query()->whereRaw('1 = 0');
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (! $this->relationLoaded('role')) {
            $this->loadMissing('role.permissions');
        } else {
            $this->role?->loadMissing('permissions');
        }

        return (bool) $this->role?->permissions?->contains('name', $permission);
    }

    public function canAccessCompany(?int $companyId): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $companyId !== null && (int) $this->company_id === (int) $companyId;
    }
}
