<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeHoliday;
use App\Models\TimeEntry;
use App\Services\Concerns\ClassifiesWorkDays;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FrequencySheetPdfExporter
{
    use ClassifiesWorkDays;

    private const MAX_DAYS = 31;

    public function generate(Employee $employee, int $month, int $year): StreamedResponse
    {
        $data = $this->prepareData($employee, $month, $year);

        $pdf = Pdf::loadView('exports.frequency-sheet-pdf', $data)
            ->setPaper('a4', 'portrait');

        $fileName = "frequencia-{$employee->inscription}-{$month}-{$year}.pdf";

        return response()->streamDownload(function () use ($pdf): void {
            echo $pdf->output();
        }, $fileName, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * @param  Collection<int, Employee>  $employees
     */
    public function generateBatch(Collection $employees, int $month, int $year): StreamedResponse
    {
        $sheets = $employees->map(fn (Employee $employee): array => $this->prepareData($employee, $month, $year));

        $pdf = Pdf::loadView('exports.frequency-sheet-pdf-batch', ['sheets' => $sheets])
            ->setPaper('a4', 'portrait');

        $monthNames = [
            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Marco', 4 => 'Abril',
            5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
            9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
        ];

        $fileName = "frequencias-{$monthNames[$month]}-{$year}.pdf";

        return response()->streamDownload(function () use ($pdf): void {
            echo $pdf->output();
        }, $fileName, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function prepareData(Employee $employee, int $month, int $year): array
    {
        $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();
        $daysInMonth = $endOfMonth->day;
        $today = Carbon::today();

        $timeEntries = TimeEntry::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->get()
            ->keyBy(fn (TimeEntry $entry): string => $entry->date->format('Y-m-d'));

        $holidays = $this->getHolidaysForMonth($startOfMonth);

        $employeeHolidayReasons = EmployeeHoliday::query()
            ->where('employee_id', $employee->id)
            ->whereIn('holiday_id', $holidays->pluck('id'))
            ->pluck('reason', 'holiday_id');

        $days = [];
        $observations = [];

        for ($day = 1; $day <= self::MAX_DAYS; $day++) {
            $dayData = [
                'number' => str_pad((string) $day, 2, '0', STR_PAD_LEFT),
                'type' => 'blank',
                'morning_entry' => null,
                'morning_exit' => null,
                'afternoon_entry' => null,
                'afternoon_exit' => null,
                'rubrica' => null,
            ];

            if ($day > $daysInMonth) {
                $days[] = $dayData;

                continue;
            }

            $date = Carbon::create($year, $month, $day);
            $dateKey = $date->format('Y-m-d');
            $entry = $timeEntries->get($dateKey);
            $classification = $this->classifyDay($date, $entry, $holidays, $employeeHolidayReasons, $today);

            if ($classification['observation'] && $classification['type'] !== 'absent' && $classification['type'] !== 'weekend') {
                $typeLabel = match ($classification['type']) {
                    'holiday' => 'Feriado',
                    'optional' => 'Ponto Facultativo',
                    'dispensation' => 'Dispensa',
                    default => '',
                };
                $observations[] = "Dia {$day} - {$typeLabel}: {$classification['observation']}";
            }

            $dayData['type'] = $classification['type'];

            if ($classification['type'] === 'present' && $entry) {
                $dayData['morning_entry'] = $entry->morning_entry;
                $dayData['morning_exit'] = $entry->morning_exit;
                $dayData['afternoon_entry'] = $entry->afternoon_entry;
                $dayData['afternoon_exit'] = $entry->afternoon_exit;
                $dayData['rubrica'] = $employee->name;
            }

            $days[] = $dayData;
        }

        $monthNames = [
            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
            5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
            9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
        ];

        return [
            'employee' => $employee,
            'month' => $month,
            'year' => $year,
            'monthName' => $monthNames[$month],
            'period' => $startOfMonth->format('d/m/Y').' A '.$endOfMonth->format('d/m/Y'),
            'days' => $days,
            'observations' => $observations,
        ];
    }
}
