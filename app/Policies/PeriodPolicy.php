<?php

namespace App\Policies;

use App\Models\Period;
use App\Models\User;

class PeriodPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdministrator();
    }

    public function view(User $user, Period $period): bool
    {
        return $user->isAdministrator();
    }

    public function create(User $user): bool
    {
        return $user->isAdministrator();
    }

    public function update(User $user, Period $period): bool
    {
        return $user->isAdministrator();
    }

    public function delete(User $user, Period $period): bool
    {
        return $user->isAdministrator();
    }

    public function restore(User $user, Period $period): bool
    {
        return $user->isAdministrator();
    }

    public function forceDelete(User $user, Period $period): bool
    {
        return $user->isAdministrator();
    }
}
