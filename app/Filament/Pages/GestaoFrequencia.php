<?php

namespace App\Filament\Pages;

use App\Enums\NavigationGroup;
use App\Enums\Turno;
use App\Models\Employee;
use App\Models\Period;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class GestaoFrequencia extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.gestao-frequencia';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|\UnitEnum|null $navigationGroup = NavigationGroup::Attendance;

    protected static ?string $navigationLabel = 'Gestão de Frequência';

    protected static ?string $title = 'Gestão de Frequência';

    public ?int $selectedPeriodId = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->isManagerOrHigher() ?? false;
    }

    public function mount(): void
    {
        $current = Period::query()->orderByDesc('year')->orderByDesc('month')->first();
        $this->selectedPeriodId = $current?->id;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('selectPeriod')
                ->label('Selecionar Período')
                ->icon(Heroicon::OutlinedCalendarDays)
                ->color('gray')
                ->schema([
                    Select::make('period_id')
                        ->label('Período')
                        ->options(
                            Period::query()
                                ->orderByDesc('year')
                                ->orderByDesc('month')
                                ->get()
                                ->mapWithKeys(fn (Period $p) => [
                                    $p->id => $this->formatPeriodLabel($p),
                                ])
                        )
                        ->default($this->selectedPeriodId)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->selectedPeriodId = $data['period_id'];
                    $this->resetTable();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Employee::query()
                    ->where('active', true)
                    ->orderBy('name')
            )
            ->columns([
                TextColumn::make('registration_number')
                    ->label('Matrícula')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('department')
                    ->label('Lotação')
                    ->searchable(),
                TextColumn::make('shift')
                    ->label('Turno')
                    ->badge()
                    ->color(fn (Turno $state): string => match ($state) {
                        Turno::Morning => 'info',
                        Turno::Afternoon => 'warning',
                    }),
                IconColumn::make('active')
                    ->label('Ativo')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('department')
                    ->label('Lotação')
                    ->options(
                        Employee::query()
                            ->distinct()
                            ->orderBy('department')
                            ->pluck('department', 'department')
                    ),
                SelectFilter::make('shift')
                    ->label('Turno')
                    ->options(Turno::class),
            ])
            ->recordActions([
                Action::make('viewFrequency')
                    ->label('Ver Frequência')
                    ->icon(Heroicon::OutlinedEye)
                    ->color('primary')
                    ->url(function (Employee $record): string {
                        $periodId = $this->selectedPeriodId
                            ?? Period::query()->orderByDesc('year')->orderByDesc('month')->first()?->id;

                        return FrequenciaServidor::getUrl([
                            'employee' => $record->id,
                            'period' => $periodId,
                        ]);
                    }),
            ])
            ->emptyStateHeading('Nenhum servidor encontrado')
            ->emptyStateIcon(Heroicon::OutlinedUsers);
    }

    private function formatPeriodLabel(Period $period): string
    {
        $months = [
            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
            5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
            9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
        ];

        return ($months[$period->month] ?? $period->month).' / '.$period->year
            .' — '.$period->status->getLabel();
    }
}
