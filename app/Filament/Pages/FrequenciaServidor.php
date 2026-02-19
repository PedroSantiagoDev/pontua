<?php

namespace App\Filament\Pages;

use App\Enums\NavigationGroup;
use App\Enums\StatusPeriodo;
use App\Enums\TipoObservacao;
use App\Models\AttendanceNote;
use App\Models\Employee;
use App\Models\Period;
use App\Services\PontoService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

class FrequenciaServidor extends Page
{
    protected string $view = 'filament.pages.frequencia-servidor';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|\UnitEnum|null $navigationGroup = NavigationGroup::Attendance;

    protected static ?string $navigationLabel = 'Frequência do Servidor';

    protected static bool $shouldRegisterNavigation = false;

    public Employee $employee;

    public Period $period;

    public static function canAccess(): bool
    {
        return auth()->user()?->isManagerOrHigher() ?? false;
    }

    public function mount(): void
    {
        $employeeId = request()->query('employee');
        $periodId = request()->query('period');

        abort_unless($employeeId && $periodId, 404);

        $this->employee = Employee::findOrFail($employeeId);
        $this->period = Period::findOrFail($periodId);
    }

    public function getTitle(): string
    {
        return 'Frequência: '.$this->employee->name;
    }

    protected function getHeaderActions(): array
    {
        $periodDays = range(1, $this->period->end_date->day);

        return [
            Action::make('addNote')
                ->label('Adicionar Observação')
                ->icon(Heroicon::OutlinedPencilSquare)
                ->color('warning')
                ->visible(fn () => $this->period->status === StatusPeriodo::Open || auth()->user()->isAdministrator())
                ->schema([
                    Select::make('day')
                        ->label('Dia')
                        ->options(collect($periodDays)->mapWithKeys(fn (int $d) => [$d => str_pad((string) $d, 2, '0', STR_PAD_LEFT)]))
                        ->required(),
                    Select::make('type')
                        ->label('Tipo')
                        ->options(TipoObservacao::class)
                        ->required()
                        ->live(),
                    Textarea::make('notes')
                        ->label('Descrição Livre')
                        ->rows(3)
                        ->visible(fn (Get $get): bool => $get('type') === TipoObservacao::Free->value),
                ])
                ->action(function (array $data): void {
                    $existing = AttendanceNote::query()
                        ->where('employee_id', $this->employee->id)
                        ->where('period_id', $this->period->id)
                        ->where('day', $data['day'])
                        ->exists();

                    if ($existing) {
                        Notification::make()
                            ->title('Já existe uma observação para este dia.')
                            ->danger()
                            ->send();

                        return;
                    }

                    AttendanceNote::create([
                        'employee_id' => $this->employee->id,
                        'period_id' => $this->period->id,
                        'day' => $data['day'],
                        'type' => $data['type'],
                        'notes' => $data['notes'] ?? null,
                        'created_by' => auth()->id(),
                    ]);

                    Notification::make()
                        ->title('Observação adicionada com sucesso.')
                        ->success()
                        ->send();
                }),

            Action::make('exportPdf')
                ->label('Exportar PDF')
                ->icon(Heroicon::OutlinedDocumentText)
                ->color('danger')
                ->url(route('attendance.export.pdf', [
                    'employee' => $this->employee->id,
                    'period' => $this->period->id,
                ]))
                ->openUrlInNewTab(),

            Action::make('exportExcel')
                ->label('Exportar Excel')
                ->icon(Heroicon::OutlinedTableCells)
                ->color('success')
                ->url(route('attendance.export.excel', [
                    'employee' => $this->employee->id,
                    'period' => $this->period->id,
                ]))
                ->openUrlInNewTab(),
        ];
    }

    protected function getViewData(): array
    {
        $service = app(PontoService::class);
        $holidays = $service->getHolidaysForPeriod($this->period);
        $notes = $service->getNotesForPeriod($this->employee, $this->period);
        $entries = $service->getEntriesForPeriod($this->employee, $this->period);
        $days = $service->buildMonthCalendar(
            $this->period,
            $holidays,
            $notes,
            $entries,
            $this->employee->shift,
        );

        $months = [
            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
            5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
            9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
        ];

        return [
            'employee' => $this->employee,
            'period' => $this->period,
            'days' => $days,
            'monthName' => $months[$this->period->month] ?? $this->period->month,
        ];
    }
}
