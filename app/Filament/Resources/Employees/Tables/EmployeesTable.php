<?php

namespace App\Filament\Resources\Employees\Tables;

use App\Models\Employee;
use App\Services\FrequencySheetExporter;
use App\Services\FrequencySheetPdfExporter;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('inscription')
                    ->label('Matrícula')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('department')
                    ->label('Lotação')
                    ->sortable(),

                TextColumn::make('position')
                    ->label('Cargo/Função')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('organization')
                    ->label('Organização')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('default_shift')
                    ->label('Turno')
                    ->badge()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make(),
                self::exportExcelAction(),
                self::exportPdfAction(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function exportExcelAction(): Action
    {
        $currentMonth = (int) now()->month;
        $currentYear = (int) now()->year;

        return Action::make('exportExcel')
            ->label('Exportar Frequência')
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->color('success')
            ->modalHeading('Exportar Folha de Frequência')
            ->modalSubmitActionLabel('Exportar')
            ->schema([
                Select::make('month')
                    ->label('Mês')
                    ->options([
                        1 => 'Janeiro',
                        2 => 'Fevereiro',
                        3 => 'Março',
                        4 => 'Abril',
                        5 => 'Maio',
                        6 => 'Junho',
                        7 => 'Julho',
                        8 => 'Agosto',
                        9 => 'Setembro',
                        10 => 'Outubro',
                        11 => 'Novembro',
                        12 => 'Dezembro',
                    ])
                    ->default($currentMonth)
                    ->required(),

                Select::make('year')
                    ->label('Ano')
                    ->options(
                        collect(range($currentYear - 2, $currentYear))
                            ->mapWithKeys(fn (int $y): array => [$y => (string) $y])
                            ->all()
                    )
                    ->default($currentYear)
                    ->required(),
            ])
            ->action(function (array $data, Employee $record): StreamedResponse {
                $exporter = new FrequencySheetExporter;
                $spreadsheet = $exporter->generate($record, (int) $data['month'], (int) $data['year']);

                $fileName = "frequencia-{$record->inscription}-{$data['month']}-{$data['year']}.xlsx";

                return response()->streamDownload(function () use ($spreadsheet): void {
                    $writer = new Xlsx($spreadsheet);
                    $writer->save('php://output');
                }, $fileName, [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ]);
            })
            ->visible(fn (): bool => auth()->user()->isAdmin() || auth()->user()->isManager());
    }

    private static function exportPdfAction(): Action
    {
        $currentMonth = (int) now()->month;
        $currentYear = (int) now()->year;

        return Action::make('exportPdf')
            ->label('Exportar PDF')
            ->icon(Heroicon::OutlinedDocumentText)
            ->color('danger')
            ->modalHeading('Exportar Folha de Frequência (PDF)')
            ->modalSubmitActionLabel('Exportar')
            ->schema([
                Select::make('month')
                    ->label('Mês')
                    ->options([
                        1 => 'Janeiro',
                        2 => 'Fevereiro',
                        3 => 'Março',
                        4 => 'Abril',
                        5 => 'Maio',
                        6 => 'Junho',
                        7 => 'Julho',
                        8 => 'Agosto',
                        9 => 'Setembro',
                        10 => 'Outubro',
                        11 => 'Novembro',
                        12 => 'Dezembro',
                    ])
                    ->default($currentMonth)
                    ->required(),

                Select::make('year')
                    ->label('Ano')
                    ->options(
                        collect(range($currentYear - 2, $currentYear))
                            ->mapWithKeys(fn (int $y): array => [$y => (string) $y])
                            ->all()
                    )
                    ->default($currentYear)
                    ->required(),
            ])
            ->action(function (array $data, Employee $record): StreamedResponse {
                $exporter = new FrequencySheetPdfExporter;

                return $exporter->generate($record, (int) $data['month'], (int) $data['year']);
            })
            ->visible(fn (): bool => auth()->user()->isAdmin() || auth()->user()->isManager());
    }
}
