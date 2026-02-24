<?php

namespace App\Filament\Widgets;

use App\Enums\Shift;
use App\Models\Employee;
use App\Models\TimeEntry;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class ClockInWidget extends Widget
{
    protected string $view = 'filament.widgets.clock-in-widget';

    protected int|string|array $columnSpan = 'full';

    public ?int $employeeId = null;

    public ?string $defaultShiftValue = null;

    public ?string $selectedShift = null;

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user && $user->isEmployee() && $user->employee !== null;
    }

    public function mount(): void
    {
        $employee = auth()->user()->employee;
        $this->employeeId = $employee->id;
        $this->defaultShiftValue = $employee->default_shift->value;

        $todayEntry = $this->getTodayEntry();

        $this->selectedShift = $todayEntry?->shift_override?->value
            ?? $this->defaultShiftValue;
    }

    public function getEmployee(): Employee
    {
        return Employee::find($this->employeeId);
    }

    public function getTodayEntry(): ?TimeEntry
    {
        return TimeEntry::where('employee_id', $this->employeeId)
            ->whereDate('date', Carbon::today())
            ->first();
    }

    /**
     * @return array{field: string, label: string}|null
     */
    public function getNextField(): ?array
    {
        $entry = $this->getTodayEntry();
        $shift = Shift::from($this->selectedShift);

        if ($shift === Shift::Morning) {
            if (! $entry || $entry->morning_entry === null) {
                return ['field' => 'morning_entry', 'label' => 'Entrada Manhã'];
            }
            if ($entry->morning_exit === null) {
                return ['field' => 'morning_exit', 'label' => 'Saída Manhã'];
            }
        }

        if ($shift === Shift::Afternoon) {
            if (! $entry || $entry->afternoon_entry === null) {
                return ['field' => 'afternoon_entry', 'label' => 'Entrada Tarde'];
            }
            if ($entry->afternoon_exit === null) {
                return ['field' => 'afternoon_exit', 'label' => 'Saída Tarde'];
            }
        }

        return null;
    }

    public function clockIn(): void
    {
        $nextField = $this->getNextField();

        if (! $nextField) {
            Notification::make()
                ->title('Todos os pontos do turno já foram marcados hoje.')
                ->warning()
                ->send();

            return;
        }

        $now = Carbon::now()->format('H:i');
        $shift = Shift::from($this->selectedShift);
        $defaultShift = Shift::from($this->defaultShiftValue);
        $shiftOverride = $shift !== $defaultShift ? $shift : null;

        $entry = $this->getTodayEntry();

        if (! $entry) {
            $entry = TimeEntry::create([
                'employee_id' => $this->employeeId,
                'date' => Carbon::today()->toDateString(),
                'shift_override' => $shiftOverride,
            ]);
        } elseif ($shiftOverride && $entry->shift_override !== $shiftOverride) {
            $entry->update(['shift_override' => $shiftOverride]);
        }

        $entry->update([
            $nextField['field'] => $now,
        ]);

        Notification::make()
            ->title('Ponto marcado!')
            ->body("{$nextField['label']} registrado às {$now}.")
            ->success()
            ->send();
    }

    /**
     * @return array{morning_entry: ?string, morning_exit: ?string, afternoon_entry: ?string, afternoon_exit: ?string}
     */
    public function getTodayEntries(): array
    {
        $entry = $this->getTodayEntry();

        return [
            'morning_entry' => $entry?->morning_entry,
            'morning_exit' => $entry?->morning_exit,
            'afternoon_entry' => $entry?->afternoon_entry,
            'afternoon_exit' => $entry?->afternoon_exit,
        ];
    }
}
