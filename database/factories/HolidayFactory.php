<?php

namespace Database\Factories;

use App\Enums\TipoFeriado;
use App\Models\Holiday;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Holiday>
 */
class HolidayFactory extends Factory
{
    public function definition(): array
    {
        return [
            'date' => fake()->unique()->dateTimeBetween('2026-01-01', '2026-12-31')->format('Y-m-d'),
            'description' => fake()->words(3, true),
            'type' => fake()->randomElement(TipoFeriado::cases()),
        ];
    }

    public function national(): static
    {
        return $this->state(fn (array $attributes) => ['type' => TipoFeriado::National]);
    }

    public function state(): static
    {
        return $this->state(fn (array $attributes) => ['type' => TipoFeriado::State]);
    }

    public function municipal(): static
    {
        return $this->state(fn (array $attributes) => ['type' => TipoFeriado::Municipal]);
    }
}
