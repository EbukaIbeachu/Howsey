<?php

namespace App\Policies;

use App\Models\User;

class BasePolicy
{
    protected string $module = '';

    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermission($this->module.'.view');
    }

    public function view(User $user, $model): bool
    {
        return $user->hasPermission($this->module.'.view') && $user->canAccessCompany((int) $model->company_id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission($this->module.'.create');
    }

    public function update(User $user, $model): bool
    {
        return $user->hasPermission($this->module.'.edit') && $user->canAccessCompany((int) $model->company_id);
    }

    public function delete(User $user, $model): bool
    {
        return $user->hasPermission($this->module.'.delete') && $user->canAccessCompany((int) $model->company_id);
    }
}
