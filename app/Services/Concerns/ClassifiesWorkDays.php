<?php

namespace App\Services\Concerns;

use App\Enums\HolidayScope;
use App\Enums\HolidayType;
use App\Models\Holiday;
use App\Models\TimeEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

trait ClassifiesWorkDays
{
    /**
     * @return array{type: string, observation: ?string}
     */
    private function classifyDay(
        Carbon $date,
        ?TimeEntry $entry,
        Collection $holidays,
        Collection $employeeHolidayReasons,
        Carbon $today,
    ): array {
        if ($date->isWeekend()) {
            return ['type' => 'weekend', 'observation' => 'Fim de semana'];
        }

        $holiday = $holidays->first(function (Holiday $h) use ($date): bool {
            if ($h->recurrent) {
                return $h->date->month === $date->month && $h->date->day === $date->day;
            }

            return $h->date->format('Y-m-d') === $date->format('Y-m-d');
        });

        if ($holiday) {
            if ($holiday->type === HolidayType::Holiday && $holiday->scope === HolidayScope::All) {
                return ['type' => 'holiday', 'observation' => $holiday->name];
            }

            if ($holiday->type === HolidayType::Optional && $holiday->scope === HolidayScope::All) {
                return ['type' => 'optional', 'observation' => $holiday->name];
            }

            if ($holiday->scope === HolidayScope::Partial && $employeeHolidayReasons->has($holiday->id)) {
                $reason = $employeeHolidayReasons->get($holiday->id);
                $typeLabel = $holiday->type === HolidayType::Holiday ? 'Feriado' : ($holiday->type === HolidayType::Partial ? 'Dispensa' : 'Ponto Facultativo');

                return ['type' => $holiday->type === HolidayType::Partial ? 'dispensation' : 'holiday', 'observation' => "{$typeLabel}: {$holiday->name}".($reason ? " ({$reason})" : '')];
            }

            if ($holiday->type === HolidayType::Partial && $holiday->scope === HolidayScope::All) {
                return ['type' => 'dispensation', 'observation' => "Dispensa: {$holiday->name}"];
            }
        }

        if ($date->gt($today)) {
            return ['type' => 'future', 'observation' => null];
        }

        if ($entry && ($entry->morning_entry || $entry->morning_exit || $entry->afternoon_entry || $entry->afternoon_exit)) {
            return ['type' => 'present', 'observation' => null];
        }

        return ['type' => 'absent', 'observation' => 'FALTA'];
    }

    private function getHolidaysForMonth(Carbon $startOfMonth): Collection
    {
        $month = $startOfMonth->month;
        $year = $startOfMonth->year;

        return Holiday::query()
            ->where(function ($query) use ($month, $year): void {
                $query->where(function ($q) use ($month, $year): void {
                    $q->where('recurrent', false)
                        ->whereMonth('date', $month)
                        ->whereYear('date', $year);
                })->orWhere(function ($q) use ($month): void {
                    $q->where('recurrent', true)
                        ->whereMonth('date', $month);
                });
            })
            ->get();
    }
}
