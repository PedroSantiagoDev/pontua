<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Resources\Employees\EmployeeResource;
use App\Models\Employee;
use App\Services\FrequencySheetExporter;
use App\Services\FrequencySheetPdfExporter;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            $this->batchExportExcelAction(),
            $this->batchExportPdfAction(),
        ];
    }

    private function batchExportExcelAction(): Action
    {
        $currentMonth = (int) now()->month;
        $currentYear = (int) now()->year;

        return Action::make('batchExportExcel')
            ->label('Exportar Todos (Excel)')
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->color('success')
            ->modalHeading('Exportar Frequência de Todos os Colaboradores (Excel)')
            ->modalSubmitActionLabel('Exportar')
            ->schema([
                Select::make('month')
                    ->label('Mês')
                    ->options([
                        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março',
                        4 => 'Abril', 5 => 'Maio', 6 => 'Junho',
                        7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro',
                        10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
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
            ->action(function (array $data): StreamedResponse {
                $employees = Employee::query()->orderBy('name')->get();
                $exporter = new FrequencySheetExporter;
                $spreadsheet = $exporter->generateBatch($employees, (int) $data['month'], (int) $data['year']);

                $monthNames = [
                    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Marco', 4 => 'Abril',
                    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
                    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
                ];

                $fileName = "frequencias-{$monthNames[(int) $data['month']]}-{$data['year']}.xlsx";

                return response()->streamDownload(function () use ($spreadsheet): void {
                    $writer = new Xlsx($spreadsheet);
                    $writer->save('php://output');
                }, $fileName, [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ]);
            })
            ->visible(fn (): bool => auth()->user()->isAdmin() || auth()->user()->isManager());
    }

    private function batchExportPdfAction(): Action
    {
        $currentMonth = (int) now()->month;
        $currentYear = (int) now()->year;

        return Action::make('batchExportPdf')
            ->label('Exportar Todos (PDF)')
            ->icon(Heroicon::OutlinedDocumentText)
            ->color('danger')
            ->modalHeading('Exportar Frequência de Todos os Colaboradores (PDF)')
            ->modalSubmitActionLabel('Exportar')
            ->schema([
                Select::make('month')
                    ->label('Mês')
                    ->options([
                        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março',
                        4 => 'Abril', 5 => 'Maio', 6 => 'Junho',
                        7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro',
                        10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
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
            ->action(function (array $data): StreamedResponse {
                $employees = Employee::query()->orderBy('name')->get();
                $exporter = new FrequencySheetPdfExporter;

                return $exporter->generateBatch($employees, (int) $data['month'], (int) $data['year']);
            })
            ->visible(fn (): bool => auth()->user()->isAdmin() || auth()->user()->isManager());
    }
}
