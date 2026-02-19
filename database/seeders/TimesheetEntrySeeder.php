<?php

namespace Database\Seeders;

use App\Enums\TipoBatida;
use App\Enums\Turno;
use App\Models\Employee;
use App\Models\Period;
use App\Models\TimesheetEntry;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TimesheetEntrySeeder extends Seeder
{
    public function run(): void
    {
        $currentPeriod = Period::whereMonth('start_date', now()->month)
            ->whereYear('start_date', now()->year)
            ->first();

        if (! $currentPeriod) {
            return;
        }

        $employees = Employee::where('active', true)->get();
        $today = Carbon::now()->day;

        foreach ($employees as $employee) {
            // Seed entries for business days from day 1 to yesterday
            for ($day = 1; $day < $today; $day++) {
                $date = Carbon::createFromDate($currentPeriod->year, $currentPeriod->month, $day);

                // Skip weekends
                if ($date->isWeekend()) {
                    continue;
                }

                $punchTypes = $employee->shift === Turno::Morning
                    ? [TipoBatida::MorningEntry, TipoBatida::MorningExit]
                    : [TipoBatida::AfternoonEntry, TipoBatida::AfternoonExit];

                foreach ($punchTypes as $punchType) {
                    TimesheetEntry::create([
                        'employee_id' => $employee->id,
                        'period_id' => $currentPeriod->id,
                        'day' => $day,
                        'punch_type' => $punchType,
                        'recorded_at' => $this->timeForPunchType($punchType),
                    ]);
                }
            }
        }
    }

    private function timeForPunchType(TipoBatida $punchType): string
    {
        return match ($punchType) {
            TipoBatida::MorningEntry => '08:'.str_pad(rand(0, 15), 2, '0', STR_PAD_LEFT).':00',
            TipoBatida::MorningExit => '12:'.str_pad(rand(0, 15), 2, '0', STR_PAD_LEFT).':00',
            TipoBatida::AfternoonEntry => '13:'.str_pad(rand(0, 15), 2, '0', STR_PAD_LEFT).':00',
            TipoBatida::AfternoonExit => '17:'.str_pad(rand(0, 15), 2, '0', STR_PAD_LEFT).':00',
        };
    }
}
