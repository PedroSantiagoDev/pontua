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

        $this->selectedShift =
            $todayEntry?->shift_override?->value ?? $this->defaultShiftValue;
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
     * Retorna o último campo vazio da sequência do turno.
     * Campos anteriores vazios serão preenchidos automaticamente no clockIn().
     *
     * @return array{field: string, label: string}|null
     */
    public function getNextField(): ?array
    {
        $entry = $this->getTodayEntry();
        $shift = Shift::from($this->selectedShift);
        $fields = $shift->getFieldSequence();

        $lastNull = null;

        foreach ($fields as $field => $label) {
            if (! $entry || $entry->$field === null) {
                $lastNull = ['field' => $field, 'label' => $label];
            }
        }

        return $lastNull;
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

        $shift = Shift::from($this->selectedShift);
        $defaultShift = Shift::from($this->defaultShiftValue);
        $shiftOverride = $shift !== $defaultShift ? $shift : null;
        $fixedTimes = $shift->getFixedTimes();
        $fields = $shift->getFieldSequence();

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

        // Preenche todos os campos nulos até o campo alvo (inclusive)
        $updates = [];
        foreach ($fields as $field => $label) {
            if ($entry->$field === null) {
                $updates[$field] = $fixedTimes[$field];
            }
            if ($field === $nextField['field']) {
                break;
            }
        }

        $entry->update($updates);

        $autoFilled = array_filter($updates, fn (string $field): bool => $field !== $nextField['field'], ARRAY_FILTER_USE_KEY);

        $body = "{$nextField['label']} registrado às {$fixedTimes[$nextField['field']]}.";

        if (! empty($autoFilled)) {
            $autoFilledLabels = array_map(fn (string $field): string => "{$fields[$field]} ({$fixedTimes[$field]})", array_keys($autoFilled));
            $body .= ' Pontos anteriores preenchidos automaticamente: '.implode(', ', $autoFilledLabels).'.';
        }

        Notification::make()
            ->title('Ponto marcado!')
            ->body($body)
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
