<?php

namespace App\Policies;

use App\Models\AttendanceNote;
use App\Models\User;

class AttendanceNotePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isManagerOrHigher();
    }

    public function view(User $user, AttendanceNote $attendanceNote): bool
    {
        return $user->isManagerOrHigher();
    }

    public function create(User $user): bool
    {
        return $user->isManagerOrHigher();
    }

    public function update(User $user, AttendanceNote $attendanceNote): bool
    {
        return $user->isManagerOrHigher();
    }

    public function delete(User $user, AttendanceNote $attendanceNote): bool
    {
        return $user->isAdministrator();
    }

    public function restore(User $user, AttendanceNote $attendanceNote): bool
    {
        return $user->isAdministrator();
    }

    public function forceDelete(User $user, AttendanceNote $attendanceNote): bool
    {
        return $user->isAdministrator();
    }
}
