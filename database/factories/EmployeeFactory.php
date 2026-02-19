<?php

namespace Database\Factories;

use App\Enums\Turno;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'registration_number' => fake()->unique()->numerify('######'),
            'name' => fake()->name(),
            'department' => fake()->randomElement([
                'Administração Geral',
                'Recursos Humanos',
                'Financeiro',
                'Tecnologia da Informação',
                'Assistência Social',
            ]),
            'position' => fake()->jobTitle(),
            'shift' => fake()->randomElement(Turno::cases()),
            'payroll_code' => fake()->optional()->numerify('RUB-####'),
            'active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['active' => false]);
    }

    public function morning(): static
    {
        return $this->state(fn (array $attributes) => ['shift' => Turno::Morning]);
    }

    public function afternoon(): static
    {
        return $this->state(fn (array $attributes) => ['shift' => Turno::Afternoon]);
    }
}
