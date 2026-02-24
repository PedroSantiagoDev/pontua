<?php

namespace Database\Factories;

use App\Enums\Shift;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'inscription' => fake()->unique()->numerify('######'),
            'department' => fake()->randomElement(['TI', 'RH', 'Financeiro', 'Administrativo']),
            'position' => fake()->randomElement(['Analista', 'Assistente', 'Coordenador', 'Gerente']),
            'organization' => 'AGED-MA',
            'default_shift' => fake()->randomElement(Shift::cases()),
            'user_id' => User::factory()->state(['role' => UserRole::Employee]),
        ];
    }

    public function morning(): static
    {
        return $this->state(fn (array $attributes) => [
            'default_shift' => Shift::Morning,
        ]);
    }

    public function afternoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'default_shift' => Shift::Afternoon,
        ]);
    }
}
