<?php

namespace Database\Factories;

use App\Enums\TipoObservacao;
use App\Models\AttendanceNote;
use App\Models\Employee;
use App\Models\Period;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceNote>
 */
class AttendanceNoteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'period_id' => Period::factory(),
            'day' => fake()->numberBetween(1, 28),
            'type' => fake()->randomElement(TipoObservacao::cases()),
            'notes' => fake()->optional()->sentence(),
            'created_by' => User::factory(),
        ];
    }
}
