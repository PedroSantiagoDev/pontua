<?php

namespace App\Models;

use App\Enums\StatusPeriodo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Period extends Model
{
    /** @use HasFactory<\Database\Factories\PeriodFactory> */
    use HasFactory;

    protected $fillable = [
        'month',
        'year',
        'start_date',
        'end_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'month' => 'integer',
            'year' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => StatusPeriodo::class,
        ];
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
