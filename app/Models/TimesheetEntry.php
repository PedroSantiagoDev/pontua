<?php

namespace App\Models;

use App\Enums\TipoBatida;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimesheetEntry extends Model
{
    /** @use HasFactory<\Database\Factories\TimesheetEntryFactory> */
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'period_id',
        'day',
        'punch_type',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'day' => 'integer',
            'punch_type' => TipoBatida::class,
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }
}
