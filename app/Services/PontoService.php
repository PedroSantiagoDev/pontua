<?php

namespace App\Services;

use App\Enums\StatusPeriodo;
use App\Enums\TipoBatida;
use App\Enums\Turno;
use App\Models\AttendanceNote;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\Period;
use App\Models\TimesheetEntry;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use RuntimeException;

class PontoService
{
    public function getCurrentPeriod(): ?Period
    {
        return Period::query()
            ->where('status', StatusPeriodo::Open)
            ->whereMonth('start_date', now()->month)
            ->whereYear('start_date', now()->year)
            ->first();
    }

    /** @return TipoBatida[] */
    public function getPunchTypesForShift(Turno $shift): array
    {
        return match ($shift) {
            Turno::Morning => [TipoBatida::MorningEntry, TipoBatida::MorningExit],
            Turno::Afternoon => [TipoBatida::AfternoonEntry, TipoBatida::AfternoonExit],
        };
    }

    /**
     * @return Collection<string, TimesheetEntry>
     */
    public function getEntriesForPeriod(Employee $employee, Period $period): Collection
    {
        return TimesheetEntry::query()
            ->where('employee_id', $employee->id)
            ->where('period_id', $period->id)
            ->get()
            ->keyBy(fn (TimesheetEntry $e) => "{$e->day}_{$e->punch_type->value}");
    }

    /**
     * @return Collection<int, AttendanceNote>
     */
    public function getNotesForPeriod(Employee $employee, Period $period): Collection
    {
        return AttendanceNote::query()
            ->where('employee_id', $employee->id)
            ->where('period_id', $period->id)
            ->get()
            ->keyBy('day');
    }

    /**
     * @return Collection<int, Holiday>
     */
    public function getHolidaysForPeriod(Period $period): Collection
    {
        return Holiday::query()
            ->whereYear('date', $period->year)
            ->whereMonth('date', $period->month)
            ->get()
            ->keyBy(fn (Holiday $h) => $h->date->day);
    }

    public function recordPunch(Employee $employee, TipoBatida $punchType): void
    {
        $period = $this->getCurrentPeriod();
        $today = now();
        $day = $today->day;

        // Rule 1: period must be open
        if (! $period) {
            throw new RuntimeException('Não há período aberto para o mês atual.');
        }

        // Rule 2: only today (enforced by this being a real-time action)
        // Rule 3: cannot be weekend
        if ($today->isWeekend()) {
            throw new RuntimeException('Não é possível registrar ponto em fins de semana.');
        }

        // Rule 4: cannot be a holiday
        $holidays = $this->getHolidaysForPeriod($period);

        if ($holidays->has($day)) {
            throw new RuntimeException('Não é possível registrar ponto em feriados.');
        }

        // Rule 5: cannot have an attendance note for today
        $hasNote = AttendanceNote::query()
            ->where('employee_id', $employee->id)
            ->where('period_id', $period->id)
            ->where('day', $day)
            ->exists();

        if ($hasNote) {
            throw new RuntimeException('Este dia possui uma observação que impede o registro de ponto.');
        }

        // Rule 6: punch type cannot be duplicated
        $alreadyRegistered = TimesheetEntry::query()
            ->where('employee_id', $employee->id)
            ->where('period_id', $period->id)
            ->where('day', $day)
            ->where('punch_type', $punchType)
            ->exists();

        if ($alreadyRegistered) {
            throw new RuntimeException('Esta batida já foi registrada.');
        }

        // Rule 7: exit requires corresponding entry already registered
        $requiredEntry = match ($punchType) {
            TipoBatida::MorningExit => TipoBatida::MorningEntry,
            TipoBatida::AfternoonExit => TipoBatida::AfternoonEntry,
            default => null,
        };

        if ($requiredEntry) {
            $entryExists = TimesheetEntry::query()
                ->where('employee_id', $employee->id)
                ->where('period_id', $period->id)
                ->where('day', $day)
                ->where('punch_type', $requiredEntry)
                ->exists();

            if (! $entryExists) {
                throw new RuntimeException('É necessário registrar a entrada antes da saída.');
            }
        }

        TimesheetEntry::create([
            'employee_id' => $employee->id,
            'period_id' => $period->id,
            'day' => $day,
            'punch_type' => $punchType,
            'recorded_at' => $today->format('H:i:s'),
        ]);
    }

    /**
     * Build a day-by-day data array for the given period.
     *
     * @param  Collection<int, Holiday>  $holidays
     * @param  Collection<int, AttendanceNote>  $notes
     * @param  Collection<string, TimesheetEntry>  $entries
     * @return array<int, array<string, mixed>>
     */
    public function buildMonthCalendar(
        Period $period,
        Collection $holidays,
        Collection $notes,
        Collection $entries,
        Turno $shift,
    ): array {
        $today = now();
        $days = [];
        $daysInMonth = Carbon::createFromDate($period->year, $period->month, 1)->daysInMonth;
        $punchTypes = $this->getPunchTypesForShift($shift);

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date = Carbon::createFromDate($period->year, $period->month, $d);
            $isToday = $date->isSameDay($today);
            $isPast = $date->lt($today) && ! $isToday;
            $isFuture = $date->gt($today) && ! $isToday;

            $dayEntries = [];
            foreach ($punchTypes as $type) {
                $key = "{$d}_{$type->value}";
                $dayEntries[$type->value] = $entries->get($key);
            }

            $hasPunchToday = collect($dayEntries)->filter()->isNotEmpty();
            $missingPunch = $isPast && ! $date->isWeekend() && ! $holidays->has($d) && ! $notes->has($d) && ! $hasPunchToday;

            $days[] = [
                'number' => $d,
                'date' => $date,
                'weekdayShort' => mb_strtoupper($date->shortDayName),
                'isWeekend' => $date->isWeekend(),
                'isToday' => $isToday,
                'isPast' => $isPast,
                'isFuture' => $isFuture,
                'holiday' => $holidays->get($d),
                'note' => $notes->get($d),
                'entries' => $dayEntries,
                'punchTypes' => $punchTypes,
                'missingPunch' => $missingPunch,
            ];
        }

        return $days;
    }
}
