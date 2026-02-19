<?php

namespace App\Filament\Pages;

use App\Enums\NavigationGroup;
use App\Enums\TipoBatida;
use App\Models\Employee;
use App\Models\Period;
use App\Services\PontoService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class BaterPonto extends Page
{
    protected string $view = 'filament.pages.bater-ponto';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|\UnitEnum|null $navigationGroup = NavigationGroup::Timesheet;

    protected static ?string $navigationLabel = 'Bater Ponto';

    protected static ?string $title = 'Bater Ponto';

    public static function canAccess(): bool
    {
        return auth()->user()?->employee_id !== null;
    }

    public function getEmployee(): Employee
    {
        return auth()->user()->employee;
    }

    public function getCurrentPeriod(): ?Period
    {
        return app(PontoService::class)->getCurrentPeriod();
    }

    public function getCalendarDays(): array
    {
        $service = app(PontoService::class);
        $employee = $this->getEmployee();
        $period = $this->getCurrentPeriod();

        if (! $period) {
            return [];
        }

        $holidays = $service->getHolidaysForPeriod($period);
        $notes = $service->getNotesForPeriod($employee, $period);
        $entries = $service->getEntriesForPeriod($employee, $period);

        return $service->buildMonthCalendar($period, $holidays, $notes, $entries, $employee->shift);
    }

    public function punch(string $punchType): void
    {
        $employee = $this->getEmployee();
        $tipoBatida = TipoBatida::from($punchType);

        try {
            app(PontoService::class)->recordPunch($employee, $tipoBatida);

            Notification::make()
                ->title('Ponto registrado com sucesso!')
                ->success()
                ->send();
        } catch (\RuntimeException $e) {
            Notification::make()
                ->title($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getViewData(): array
    {
        $employee = $this->getEmployee();
        $period = $this->getCurrentPeriod();
        $days = $this->getCalendarDays();

        return [
            'employee' => $employee,
            'period' => $period,
            'days' => $days,
        ];
    }
}
