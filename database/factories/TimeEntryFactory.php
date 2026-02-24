<?php

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TimeEntry>
 */
class TimeEntryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'date' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'morning_entry' => '08:00',
            'morning_exit' => '14:00',
            'afternoon_entry' => null,
            'afternoon_exit' => null,
            'shift_override' => null,
        ];
    }

    public function morning(): static
    {
        return $this->state(fn (array $attributes) => [
            'morning_entry' => fake()->time('H:i', '08:15'),
            'morning_exit' => fake()->time('H:i', '14:10'),
            'afternoon_entry' => null,
            'afternoon_exit' => null,
        ]);
    }

    public function afternoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'morning_entry' => null,
            'morning_exit' => null,
            'afternoon_entry' => fake()->time('H:i', '13:15'),
            'afternoon_exit' => fake()->time('H:i', '19:10'),
        ]);
    }

    public function withoutEntries(): static
    {
        return $this->state(fn (array $attributes) => [
            'morning_entry' => null,
            'morning_exit' => null,
            'afternoon_entry' => null,
            'afternoon_exit' => null,
        ]);
    }
}
