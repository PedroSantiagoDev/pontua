<?php

namespace App\Models;

use App\Enums\Turno;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
{
    /** @use HasFactory<\Database\Factories\EmployeeFactory> */
    use HasFactory;

    protected $fillable = [
        'registration_number',
        'name',
        'department',
        'position',
        'shift',
        'payroll_code',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'shift' => Turno::class,
            'active' => 'boolean',
        ];
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function timesheetEntries(): HasMany
    {
        return $this->hasMany(TimesheetEntry::class);
    }

    public function attendanceNotes(): HasMany
    {
        return $this->hasMany(AttendanceNote::class);
    }
}
