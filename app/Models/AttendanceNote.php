<?php

namespace App\Models;

use App\Enums\TipoObservacao;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceNote extends Model
{
    /** @use HasFactory<\Database\Factories\AttendanceNoteFactory> */
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'period_id',
        'day',
        'type',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'day' => 'integer',
            'type' => TipoObservacao::class,
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
