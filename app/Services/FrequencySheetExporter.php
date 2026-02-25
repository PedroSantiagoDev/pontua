<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeHoliday;
use App\Models\TimeEntry;
use App\Services\Concerns\ClassifiesWorkDays;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FrequencySheetExporter
{
    use ClassifiesWorkDays;

    private const FIRST_DAY_ROW = 17;

    private const MAX_DAYS = 31;

    public function generate(Employee $employee, int $month, int $year): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $this->writeSheet($spreadsheet->getActiveSheet(), $employee, $month, $year);

        return $spreadsheet;
    }

    /**
     * @param  Collection<int, Employee>  $employees
     */
    public function generateBatch(Collection $employees, int $month, int $year): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->removeSheetByIndex(0);

        foreach ($employees as $employee) {
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle(mb_substr($employee->name, 0, 31));
            $this->writeSheet($sheet, $employee, $month, $year);
        }

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    private function writeSheet(Worksheet $sheet, Employee $employee, int $month, int $year): void
    {
        $this->setColumnWidths($sheet);
        $this->writeHeader($sheet);
        $this->writeEmployeeData($sheet, $employee, $month, $year);
        $this->writeTableHeaders($sheet);

        $observations = $this->writeDayRows($sheet, $employee, $month, $year);

        $this->writeObservations($sheet, $observations);
        $this->writeSignatures($sheet);
        $this->applyBorders($sheet);
    }

    private function setColumnWidths(Worksheet $sheet): void
    {
        $widths = [
            'A' => 13.43, 'B' => 15, 'C' => 21, 'D' => 14.86,
            'E' => 21.29, 'F' => 14, 'G' => 20.43, 'H' => 13.43, 'I' => 25.29,
        ];

        foreach ($widths as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }
    }

    private function writeHeader(Worksheet $sheet): void
    {
        $sheet->mergeCells('A4:I4');
        $sheet->setCellValue('A4', 'ESTADO DO MARANHÃO');
        $this->styleCell($sheet, 'A4', bold: true, horizontal: Alignment::HORIZONTAL_CENTER, font: 'Times New Roman');

        $sheet->mergeCells('A5:I5');
        $sheet->setCellValue('A5', 'AGÊNCIA ESTADUAL DE DEFESA AGROPECUÁRIA DO MARANHÃO – AGED-MA');
        $this->styleCell($sheet, 'A5', bold: true, horizontal: Alignment::HORIZONTAL_CENTER, font: 'Times New Roman');

        $sheet->mergeCells('A7:I7');
        $sheet->setCellValue('A7', 'FOLHA INDIVIDUAL DE FREQÜÊNCIA');
        $this->styleCell($sheet, 'A7', bold: true, horizontal: Alignment::HORIZONTAL_CENTER, font: 'Times New Roman');
    }

    private function writeEmployeeData(Worksheet $sheet, Employee $employee, int $month, int $year): void
    {
        $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        // Row 9: labels
        $sheet->mergeCells('A9:B9');
        $sheet->setCellValue('A9', 'INSCRIÇÃO');
        $this->styleCell($sheet, 'A9', bold: true, horizontal: Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('C9:F9');
        $sheet->setCellValue('C9', 'NOME');
        $this->styleCell($sheet, 'C9', bold: true, horizontal: Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('G9:I9');
        $sheet->setCellValue('G9', 'MÊS/ANO');
        $this->styleCell($sheet, 'G9', bold: true, horizontal: Alignment::HORIZONTAL_CENTER);

        // Row 10-11: values
        $sheet->mergeCells('A10:B11');
        $sheet->setCellValue('A10', $employee->inscription);
        $this->styleCell($sheet, 'A10', horizontal: Alignment::HORIZONTAL_CENTER, vertical: Alignment::VERTICAL_CENTER);

        $sheet->mergeCells('C10:F11');
        $sheet->setCellValue('C10', $employee->name);
        $this->styleCell($sheet, 'C10', horizontal: Alignment::HORIZONTAL_CENTER, vertical: Alignment::VERTICAL_CENTER);

        $sheet->mergeCells('G10:I10');
        $sheet->setCellValue('G10', $startOfMonth->format('d/m/Y').' A '.$endOfMonth->format('d/m/Y'));
        $this->styleCell($sheet, 'G10', horizontal: Alignment::HORIZONTAL_CENTER, vertical: Alignment::VERTICAL_CENTER);

        $sheet->mergeCells('G11:I11');
        $sheet->setCellValue('G11', 'CARGO/FUNÇÃO');
        $this->styleCell($sheet, 'G11', bold: true, horizontal: Alignment::HORIZONTAL_CENTER);

        // Row 12: LOTAÇÃO + values
        $sheet->setCellValue('A12', 'LOTAÇÃO:');
        $this->styleCell($sheet, 'A12', bold: true);

        $sheet->setCellValue('C12', $employee->department);
        $this->styleCell($sheet, 'C12', horizontal: Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('G12:I13');
        $sheet->setCellValue('G12', $employee->position);
        $this->styleCell($sheet, 'G12', horizontal: Alignment::HORIZONTAL_CENTER, vertical: Alignment::VERTICAL_CENTER);
    }

    private function writeTableHeaders(Worksheet $sheet): void
    {
        // Row 14: DIA | MANHÃ | TARDE
        $sheet->mergeCells('A14:A16');
        $sheet->setCellValue('A14', 'DIA');
        $this->styleCell($sheet, 'A14', bold: true, horizontal: Alignment::HORIZONTAL_CENTER, vertical: Alignment::VERTICAL_CENTER);

        $sheet->mergeCells('B14:E14');
        $sheet->setCellValue('B14', 'MANHÃ');
        $this->styleCell($sheet, 'B14', bold: true, horizontal: Alignment::HORIZONTAL_CENTER, vertical: Alignment::VERTICAL_CENTER);

        $sheet->mergeCells('F14:I14');
        $sheet->setCellValue('F14', 'TARDE');
        $this->styleCell($sheet, 'F14', bold: true, horizontal: Alignment::HORIZONTAL_CENTER, vertical: Alignment::VERTICAL_CENTER);

        // Row 15: ENTRADA | SAIDA sub-headers
        $sheet->mergeCells('B15:C15');
        $sheet->setCellValue('B15', 'ENTRADA');
        $this->styleCell($sheet, 'B15', bold: true, horizontal: Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('D15:E15');
        $sheet->setCellValue('D15', 'SAIDA');
        $this->styleCell($sheet, 'D15', bold: true, horizontal: Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('F15:G15');
        $sheet->setCellValue('F15', 'ENTRADA');
        $this->styleCell($sheet, 'F15', bold: true, horizontal: Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('H15:I15');
        $sheet->setCellValue('H15', 'SAIDA');
        $this->styleCell($sheet, 'H15', bold: true, horizontal: Alignment::HORIZONTAL_CENTER);

        // Row 16: HORA | RUBRICA sub-sub-headers
        foreach (['B', 'D', 'F', 'H'] as $col) {
            $sheet->setCellValue("{$col}16", 'HORA');
            $this->styleCell($sheet, "{$col}16", horizontal: Alignment::HORIZONTAL_CENTER);
        }

        foreach (['C', 'E', 'G', 'I'] as $col) {
            $sheet->setCellValue("{$col}16", 'RUBRICA');
            $this->styleCell($sheet, "{$col}16", horizontal: Alignment::HORIZONTAL_CENTER);
        }
    }

    /**
     * @return array<string>
     */
    private function writeDayRows(Worksheet $sheet, Employee $employee, int $month, int $year): array
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

        $observations = [];

        for ($day = 1; $day <= self::MAX_DAYS; $day++) {
            $row = self::FIRST_DAY_ROW + $day - 1;

            $sheet->setCellValue("A{$row}", str_pad((string) $day, 2, '0', STR_PAD_LEFT));
            $this->styleCell($sheet, "A{$row}", bold: true, horizontal: Alignment::HORIZONTAL_CENTER, vertical: Alignment::VERTICAL_CENTER);
            $sheet->getRowDimension($row)->setRowHeight(32.1);

            if ($day > $daysInMonth) {
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

            if ($classification['type'] === 'weekend' || $classification['type'] === 'holiday' || $classification['type'] === 'optional' || $classification['type'] === 'dispensation' || $classification['type'] === 'future') {
                continue;
            }

            if ($classification['type'] === 'absent') {
                foreach (['B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'] as $col) {
                    $sheet->setCellValue("{$col}{$row}", 'FALTA');
                    $this->styleCell($sheet, "{$col}{$row}", horizontal: Alignment::HORIZONTAL_CENTER, vertical: Alignment::VERTICAL_CENTER);
                }

                continue;
            }

            // Present - fill time entries
            if ($entry) {
                $this->writeTimeCell($sheet, "B{$row}", $entry->morning_entry);
                $this->writeRubricaCell($sheet, "C{$row}", $entry->morning_entry ? $employee->name : null);
                $this->writeTimeCell($sheet, "D{$row}", $entry->morning_exit);
                $this->writeRubricaCell($sheet, "E{$row}", $entry->morning_exit ? $employee->name : null);
                $this->writeTimeCell($sheet, "F{$row}", $entry->afternoon_entry);
                $this->writeRubricaCell($sheet, "G{$row}", $entry->afternoon_entry ? $employee->name : null);
                $this->writeTimeCell($sheet, "H{$row}", $entry->afternoon_exit);
                $this->writeRubricaCell($sheet, "I{$row}", $entry->afternoon_exit ? $employee->name : null);
            }
        }

        return $observations;
    }

    private function writeTimeCell(Worksheet $sheet, string $cell, ?string $time): void
    {
        if ($time) {
            $sheet->setCellValue($cell, $time);
        }

        $this->styleCell($sheet, $cell, horizontal: Alignment::HORIZONTAL_CENTER, vertical: Alignment::VERTICAL_CENTER);
    }

    private function writeRubricaCell(Worksheet $sheet, string $cell, ?string $name): void
    {
        if ($name) {
            $sheet->setCellValue($cell, $name);
        }

        $this->styleCell($sheet, $cell, horizontal: Alignment::HORIZONTAL_CENTER, vertical: Alignment::VERTICAL_CENTER, size: 8);
    }

    /**
     * @param  array<string>  $observations
     */
    private function writeObservations(Worksheet $sheet, array $observations): void
    {
        $observationRow = self::FIRST_DAY_ROW + self::MAX_DAYS;

        $sheet->setCellValue("A{$observationRow}", 'OBSERVAÇÃO');
        $this->styleCell($sheet, "A{$observationRow}", bold: true, horizontal: Alignment::HORIZONTAL_CENTER);

        if (! empty($observations)) {
            $nextRow = $observationRow + 1;
            $sheet->mergeCells("A{$nextRow}:I{$nextRow}");
            $sheet->setCellValue("A{$nextRow}", implode('; ', $observations));
            $this->styleCell($sheet, "A{$nextRow}", wrapText: true);
        }
    }

    private function writeSignatures(Worksheet $sheet): void
    {
        $vistoRow = self::FIRST_DAY_ROW + self::MAX_DAYS + 2;

        $sheet->setCellValue("A{$vistoRow}", 'VISTO:');
        $this->styleCell($sheet, "A{$vistoRow}", bold: true);

        $sheet->setCellValue("F{$vistoRow}", 'VISTO');
        $this->styleCell($sheet, "F{$vistoRow}", bold: true);

        $signatureRow = $vistoRow + 5;

        $sheet->setCellValue("A{$signatureRow}", 'Responsável pela freqüência');
        $this->styleCell($sheet, "A{$signatureRow}", horizontal: Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension($signatureRow)->setRowHeight(30);

        $sheet->setCellValue("F{$signatureRow}", 'Assinatura do Chefe Imediato');
        $this->styleCell($sheet, "F{$signatureRow}", horizontal: Alignment::HORIZONTAL_CENTER);
    }

    private function applyBorders(Worksheet $sheet): void
    {
        $lastDayRow = self::FIRST_DAY_ROW + self::MAX_DAYS - 1;

        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ];

        // Header area borders
        $sheet->getStyle('A9:I12')->applyFromArray($borderStyle);

        // Table headers borders
        $sheet->getStyle('A14:I16')->applyFromArray($borderStyle);

        // Day rows borders
        $sheet->getStyle('A'.self::FIRST_DAY_ROW.":I{$lastDayRow}")->applyFromArray($borderStyle);
    }

    private function styleCell(
        Worksheet $sheet,
        string $cell,
        bool $bold = false,
        string $horizontal = Alignment::HORIZONTAL_GENERAL,
        string $vertical = Alignment::VERTICAL_BOTTOM,
        string $font = 'Arial',
        int $size = 12,
        bool $wrapText = false,
    ): void {
        $style = $sheet->getStyle($cell);
        $style->getFont()->setBold($bold)->setName($font)->setSize($size);
        $style->getAlignment()->setHorizontal($horizontal)->setVertical($vertical)->setWrapText($wrapText);
    }
}
