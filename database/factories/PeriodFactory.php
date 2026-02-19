<?php

namespace Database\Factories;

use App\Enums\StatusPeriodo;
use App\Models\Period;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Period>
 */
class PeriodFactory extends Factory
{
    public function definition(): array
    {
        $date = Carbon::now();

        return [
            'month' => $date->month,
            'year' => $date->year,
            'start_date' => $date->copy()->startOfMonth(),
            'end_date' => $date->copy()->endOfMonth(),
            'status' => StatusPeriodo::Open,
        ];
    }

    public function open(): static
    {
        return $this->state(fn (array $attributes) => ['status' => StatusPeriodo::Open]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => ['status' => StatusPeriodo::Closed]);
    }

    public function forMonth(int $month, int $year): static
    {
        $date = Carbon::createFromDate($year, $month, 1);

        return $this->state(fn (array $attributes) => [
            'month' => $month,
            'year' => $year,
            'start_date' => $date->copy()->startOfMonth(),
            'end_date' => $date->copy()->endOfMonth(),
        ]);
    }
}
