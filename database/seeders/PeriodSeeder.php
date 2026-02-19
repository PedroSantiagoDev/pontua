<?php

namespace Database\Seeders;

use App\Enums\StatusPeriodo;
use App\Models\Period;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PeriodSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // Current period — open
        Period::create([
            'month' => $now->month,
            'year' => $now->year,
            'start_date' => $now->copy()->startOfMonth(),
            'end_date' => $now->copy()->endOfMonth(),
            'status' => StatusPeriodo::Open,
        ]);

        // Previous period — closed
        $previous = $now->copy()->subMonth();

        Period::create([
            'month' => $previous->month,
            'year' => $previous->year,
            'start_date' => $previous->copy()->startOfMonth(),
            'end_date' => $previous->copy()->endOfMonth(),
            'status' => StatusPeriodo::Closed,
        ]);
    }
}
