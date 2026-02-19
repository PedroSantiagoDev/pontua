<?php

namespace Database\Factories;

use App\Enums\TipoBatida;
use App\Models\Employee;
use App\Models\Period;
use App\Models\TimesheetEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimesheetEntry>
 */
class TimesheetEntryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'period_id' => Period::factory(),
            'day' => fake()->numberBetween(1, 28),
            'punch_type' => fake()->randomElement(TipoBatida::cases()),
            'recorded_at' => fake()->time('H:i:s'),
        ];
    }
}
