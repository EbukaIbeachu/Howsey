<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class RolePolicy extends BasePolicy
{
    protected string $module = 'roles';

    public function view(User $user, $model): bool
    {
        return $user->hasPermission($this->module.'.view');
    }

    public function update(User $user, $model): bool
    {
        return $user->hasPermission($this->module.'.edit') && ! in_array($model->name, ['super_admin'], true);
    }

    public function delete(User $user, $model): bool
    {
        return $user->hasPermission($this->module.'.delete') && ! $model->is_system;
    }
}
