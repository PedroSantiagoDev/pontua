<?php

namespace App\Models;

use App\Enums\HolidayScope;
use App\Enums\HolidayType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Holiday extends Model
{
    /** @use HasFactory<\Database\Factories\HolidayFactory> */
    use HasFactory;

    protected $fillable = [
        'date',
        'name',
        'type',
        'recurrent',
        'scope',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'type' => HolidayType::class,
            'recurrent' => 'boolean',
            'scope' => HolidayScope::class,
        ];
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class)
            ->withPivot('reason')
            ->withTimestamps();
    }
}
