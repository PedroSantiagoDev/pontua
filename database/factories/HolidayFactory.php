<?php

namespace Database\Factories;

use App\Enums\HolidayScope;
use App\Enums\HolidayType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Holiday>
 */
class HolidayFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => fake()->dateTimeBetween('-1 month', '+1 month')->format('Y-m-d'),
            'name' => fake()->randomElement(['Ano Novo', 'Carnaval', 'Sexta-feira Santa', 'Tiradentes', 'Dia do Trabalho']),
            'type' => HolidayType::Holiday,
            'recurrent' => true,
            'scope' => HolidayScope::All,
        ];
    }

    public function optional(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => HolidayType::Optional,
        ]);
    }

    public function partial(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => HolidayType::Partial,
            'scope' => HolidayScope::Partial,
        ]);
    }
}
