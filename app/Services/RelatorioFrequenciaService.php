<?php

namespace App\Services;

use App\Exports\FrequenciaExport;
use App\Models\Employee;
use App\Models\Period;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RelatorioFrequenciaService
{
    public function __construct(private readonly PontoService $pontoService) {}

    public function buildReportData(Employee $employee, Period $period): array
    {
        $holidays = $this->pontoService->getHolidaysForPeriod($period);
        $notes = $this->pontoService->getNotesForPeriod($employee, $period);
        $entries = $this->pontoService->getEntriesForPeriod($employee, $period);
        $days = $this->pontoService->buildMonthCalendar($period, $holidays, $notes, $entries, $employee->shift);

        $months = [
            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
            5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
            9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
        ];

        $businessDays = collect($days)->filter(fn (array $day) => ! $day['isWeekend'] && $day['holiday'] === null && $day['note'] === null);
        $workedDays = $businessDays->filter(fn (array $day) => collect($day['entries'])->filter()->isNotEmpty())->count();
        $missingDays = $businessDays->filter(fn (array $day) => $day['missingPunch'])->count();

        return [
            'employee' => $employee,
            'period' => $period,
            'monthName' => $months[$period->month] ?? $period->month,
            'days' => $days,
            'workedDays' => $workedDays,
            'missingDays' => $missingDays,
        ];
    }

    public function exportPdf(Employee $employee, Period $period): Response
    {
        $data = $this->buildReportData($employee, $period);

        $pdf = Pdf::loadView('reports.folha-frequencia', $data)
            ->setPaper('a4', 'portrait');

        $filename = "frequencia_{$employee->registration_number}_{$period->year}_{$period->month}.pdf";

        return $pdf->download($filename);
    }

    public function exportExcel(Employee $employee, Period $period): BinaryFileResponse
    {
        $data = $this->buildReportData($employee, $period);
        $filename = "frequencia_{$employee->registration_number}_{$period->year}_{$period->month}.xlsx";

        return Excel::download(new FrequenciaExport($data), $filename);
    }
}
