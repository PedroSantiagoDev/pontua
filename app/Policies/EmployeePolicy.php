<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdministrator();
    }

    public function view(User $user, Employee $employee): bool
    {
        return $user->isAdministrator();
    }

    public function create(User $user): bool
    {
        return $user->isAdministrator();
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->isAdministrator();
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $user->isAdministrator();
    }

    public function restore(User $user, Employee $employee): bool
    {
        return $user->isAdministrator();
    }

    public function forceDelete(User $user, Employee $employee): bool
    {
        return $user->isAdministrator();
    }
}
