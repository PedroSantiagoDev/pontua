<?php

namespace App\Models;

use App\Enums\TipoFeriado;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    /** @use HasFactory<\Database\Factories\HolidayFactory> */
    use HasFactory;

    protected $fillable = [
        'date',
        'description',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'type' => TipoFeriado::class,
        ];
    }
}
