<?php

namespace App\Models;

use App\Enums\Shift;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeEntry extends Model
{
    /** @use HasFactory<\Database\Factories\TimeEntryFactory> */
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'date',
        'morning_entry',
        'morning_exit',
        'afternoon_entry',
        'afternoon_exit',
        'shift_override',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'shift_override' => Shift::class,
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
