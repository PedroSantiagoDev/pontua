<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Period;
use App\Services\RelatorioFrequenciaService;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AttendanceExportController extends Controller
{
    public function __construct(private readonly RelatorioFrequenciaService $reportService) {}

    public function pdf(Employee $employee, Period $period): Response
    {
        return $this->reportService->exportPdf($employee, $period);
    }

    public function excel(Employee $employee, Period $period): BinaryFileResponse
    {
        return $this->reportService->exportExcel($employee, $period);
    }
}
