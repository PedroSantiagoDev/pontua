<?php

namespace App\Policies;

use App\Models\TimeEntry;
use App\Models\User;

class TimeEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isManager() || $user->isEmployee();
    }

    public function view(User $user, TimeEntry $timeEntry): bool
    {
        if ($user->isAdmin() || $user->isManager()) {
            return true;
        }

        return $user->isEmployee()
            && $user->employee
            && $timeEntry->employee_id === $user->employee->id;
    }

    public function create(User $user): bool
    {
        return $user->isEmployee() && $user->employee !== null;
    }

    public function update(User $user, TimeEntry $timeEntry): bool
    {
        return $user->isEmployee()
            && $user->employee
            && $timeEntry->employee_id === $user->employee->id;
    }

    public function delete(User $user, TimeEntry $timeEntry): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }
}
