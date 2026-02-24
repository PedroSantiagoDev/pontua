<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function view(User $user, Employee $employee): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }
}
