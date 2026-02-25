<?php

namespace App\Filament\Pages;

use App\Enums\HolidayScope;
use App\Enums\HolidayType;
use App\Models\Employee;
use App\Models\EmployeeHoliday;
use App\Models\Holiday;
use App\Models\TimeEntry;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

class MyTimeEntries extends Page
{
    protected string $view = 'filament.pages.my-time-entries';

    protected static ?string $title = 'Meus Pontos';

    protected static ?string $navigationLabel = 'Meus Pontos';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?int $navigationSort = 2;

    #[Url]
    public int $selectedMonth;

    #[Url]
    public int $selectedYear;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && $user->isEmployee() && $user->employee !== null;
    }

    public function mount(): void
    {
        $this->selectedMonth = (int) now()->month;
        $this->selectedYear = (int) now()->year;
    }

    public function getEmployee(): Employee
    {
        return auth()->user()->employee;
    }

    /**
     * @return array<int, array{date: string, weekday: string, type: string, morning_entry: ?string, morning_exit: ?string, afternoon_entry: ?string, afternoon_exit: ?string, observation: ?string}>
     */
    public function getCalendarDays(): array
    {
        $employee = $this->getEmployee();
        $startOfMonth = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();
        $today = Carbon::today();

        $timeEntries = TimeEntry::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->get()
            ->keyBy(fn (TimeEntry $entry): string => $entry->date->format('Y-m-d'));

        $holidays = $this->getHolidaysForMonth($startOfMonth);

        $employeeHolidayIds = EmployeeHoliday::query()
            ->where('employee_id', $employee->id)
            ->whereIn('holiday_id', $holidays->pluck('id'))
            ->pluck('reason', 'holiday_id');

        $days = [];

        for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
            $dateKey = $date->format('Y-m-d');
            $entry = $timeEntries->get($dateKey);
            $classification = $this->classifyDay($date, $entry, $holidays, $employeeHolidayIds, $today);

            $days[] = [
                'date' => $date->format('d/m'),
                'weekday' => $this->translateWeekday($date->dayOfWeek),
                'type' => $classification['type'],
                'morning_entry' => $entry?->morning_entry,
                'morning_exit' => $entry?->morning_exit,
                'afternoon_entry' => $entry?->afternoon_entry,
                'afternoon_exit' => $entry?->afternoon_exit,
                'observation' => $classification['observation'],
            ];
        }

        return $days;
    }

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

    private function translateWeekday(int $dayOfWeek): string
    {
        return match ($dayOfWeek) {
            Carbon::SUNDAY => 'Domingo',
            Carbon::MONDAY => 'Segunda',
            Carbon::TUESDAY => 'Terça',
            Carbon::WEDNESDAY => 'Quarta',
            Carbon::THURSDAY => 'Quinta',
            Carbon::FRIDAY => 'Sexta',
            Carbon::SATURDAY => 'Sábado',
        };
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    public function getMonthOptions(): array
    {
        return [
            ['value' => 1, 'label' => 'Janeiro'],
            ['value' => 2, 'label' => 'Fevereiro'],
            ['value' => 3, 'label' => 'Março'],
            ['value' => 4, 'label' => 'Abril'],
            ['value' => 5, 'label' => 'Maio'],
            ['value' => 6, 'label' => 'Junho'],
            ['value' => 7, 'label' => 'Julho'],
            ['value' => 8, 'label' => 'Agosto'],
            ['value' => 9, 'label' => 'Setembro'],
            ['value' => 10, 'label' => 'Outubro'],
            ['value' => 11, 'label' => 'Novembro'],
            ['value' => 12, 'label' => 'Dezembro'],
        ];
    }

    /**
     * @return array<int>
     */
    public function getYearOptions(): array
    {
        $currentYear = (int) now()->year;

        return range($currentYear - 2, $currentYear);
    }
}
