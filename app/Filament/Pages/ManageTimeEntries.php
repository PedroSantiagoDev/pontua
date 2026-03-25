<?php

namespace App\Filament\Pages;

use App\Enums\HolidayScope;
use App\Enums\HolidayType;
use App\Models\Employee;
use App\Models\EmployeeHoliday;
use App\Models\Holiday;
use App\Models\TimeEntry;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TimePicker;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

class ManageTimeEntries extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.manage-time-entries';

    protected static ?string $title = 'Gerenciar Pontos';

    protected static ?string $navigationLabel = 'Gerenciar Pontos';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?int $navigationSort = 10;

    #[Url]
    public ?int $selectedEmployeeId = null;

    #[Url]
    public int $selectedMonth;

    #[Url]
    public int $selectedYear;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && ($user->isAdmin() || $user->isManager());
    }

    public function mount(): void
    {
        $lastMonth = now()->subMonth();
        $this->selectedMonth = (int) $lastMonth->month;
        $this->selectedYear = (int) $lastMonth->year;
    }

    public function table(Table $table): Table
    {
        $skipTypes = ['weekend', 'holiday', 'optional', 'dispensation'];

        return $table
            ->records(fn (): array => $this->selectedEmployeeId
                ? collect($this->getCalendarDays())
                    ->mapWithKeys(fn (array $day, int $key): array => [$key + 1 => $day])
                    ->all()
                : [])
            ->columns([
                TextColumn::make('date')
                    ->label('Dia'),
                TextColumn::make('weekday')
                    ->label('Dia da Semana'),
                TextColumn::make('morning_entry')
                    ->label('Entrada Manhã')
                    ->formatStateUsing(fn (?string $state, array $record): string => in_array($record['type'], $skipTypes) ? '' : ($state ?? '--:--')),
                TextColumn::make('morning_exit')
                    ->label('Saída Manhã')
                    ->formatStateUsing(fn (?string $state, array $record): string => in_array($record['type'], $skipTypes) ? '' : ($state ?? '--:--')),
                TextColumn::make('afternoon_entry')
                    ->label('Entrada Tarde')
                    ->formatStateUsing(fn (?string $state, array $record): string => in_array($record['type'], $skipTypes) ? '' : ($state ?? '--:--')),
                TextColumn::make('afternoon_exit')
                    ->label('Saída Tarde')
                    ->formatStateUsing(fn (?string $state, array $record): string => in_array($record['type'], $skipTypes) ? '' : ($state ?? '--:--')),
                TextColumn::make('observation')
                    ->label('Observação')
                    ->badge()
                    ->color(fn (array $record): string => match ($record['type']) {
                        'absent' => 'danger',
                        'holiday' => 'success',
                        'optional' => 'warning',
                        'dispensation' => 'info',
                        'weekend' => 'gray',
                        default => 'gray',
                    })
                    ->icon(fn (array $record): ?string => $record['type'] === 'absent' ? 'heroicon-m-exclamation-triangle' : null),
            ])
            ->recordActions([
                Action::make('edit')
                    ->label('Preencher')
                    ->icon('heroicon-m-pencil-square')
                    ->modalHeading(fn (array $record): string => "Ponto de {$record['date']} — {$record['weekday']}")
                    ->form([
                        TimePicker::make('morning_entry')
                            ->label('Entrada Manhã')
                            ->seconds(false)
                            ->displayFormat('H:i'),
                        TimePicker::make('morning_exit')
                            ->label('Saída Manhã')
                            ->seconds(false)
                            ->displayFormat('H:i'),
                        TimePicker::make('afternoon_entry')
                            ->label('Entrada Tarde')
                            ->seconds(false)
                            ->displayFormat('H:i'),
                        TimePicker::make('afternoon_exit')
                            ->label('Saída Tarde')
                            ->seconds(false)
                            ->displayFormat('H:i'),
                    ])
                    ->fillForm(fn (array $record): array => [
                        'morning_entry' => $record['morning_entry'],
                        'morning_exit' => $record['morning_exit'],
                        'afternoon_entry' => $record['afternoon_entry'],
                        'afternoon_exit' => $record['afternoon_exit'],
                    ])
                    ->action(function (array $record, array $data): void {
                        TimeEntry::updateOrCreate(
                            [
                                'employee_id' => $this->selectedEmployeeId,
                                'date' => $record['date_raw'],
                            ],
                            array_filter($data, fn (?string $value): bool => $value !== null && $value !== ''),
                        );

                        Notification::make()
                            ->title('Ponto salvo com sucesso!')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (array $record): bool => ! in_array($record['type'], ['weekend', 'future'])),
            ])
            ->recordClasses(fn (array $record): string => match ($record['type']) {
                'weekend' => 'bg-gray-50 !text-gray-400 dark:bg-gray-950 dark:!text-gray-500',
                'holiday' => 'bg-success-50 dark:bg-success-950',
                'optional' => 'bg-warning-50 dark:bg-warning-950',
                'dispensation' => 'bg-info-50 dark:bg-info-950',
                'absent' => 'bg-danger-50 dark:bg-danger-950',
                default => '',
            })
            ->paginated(false)
            ->striped(false);
    }

    /**
     * @return array<int, array{date: string, date_raw: string, weekday: string, type: string, morning_entry: ?string, morning_exit: ?string, afternoon_entry: ?string, afternoon_exit: ?string, observation: ?string}>
     */
    public function getCalendarDays(): array
    {
        $employee = Employee::find($this->selectedEmployeeId);

        if (! $employee) {
            return [];
        }

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
                'date_raw' => $dateKey,
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
     * @param  Collection<int, Holiday>  $holidays
     * @param  Collection<int, string>  $employeeHolidayReasons
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

    /**
     * @return Collection<int, Employee>
     */
    public function getEmployees(): Collection
    {
        return Employee::query()->orderBy('name')->get();
    }
}
