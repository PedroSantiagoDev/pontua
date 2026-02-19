<?php

namespace App\Exports;

use App\Enums\TipoBatida;
use App\Models\Employee;
use App\Models\Period;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FrequenciaExport implements FromArray, WithColumnWidths, WithEvents, WithTitle
{
    private array $rows = [];

    /** @param array<string, mixed> $reportData */
    public function __construct(private readonly array $reportData) {}

    public function title(): string
    {
        /** @var Employee $employee */
        $employee = $this->reportData['employee'];

        return "Frequência {$employee->registration_number}";
    }

    public function array(): array
    {
        /** @var Employee $employee */
        $employee = $this->reportData['employee'];

        /** @var Period $period */
        $period = $this->reportData['period'];

        $this->rows = [];

        // Row 1 — Title (merged A1:I1)
        $this->rows[] = ['ESTADO DO MARANHÃO — AGED-MA', '', '', '', '', '', '', '', ''];

        // Row 2 — Subtitle
        $this->rows[] = ['FOLHA INDIVIDUAL DE FREQUÊNCIA', '', '', '', '', '', '', '', ''];

        // Row 3 — blank
        $this->rows[] = ['', '', '', '', '', '', '', '', ''];

        // Row 4 — Inscrição | Nome | Mês/Ano
        $startFmt = $period->start_date->format('d/m/Y');
        $endFmt = $period->end_date->format('d/m/Y');
        $this->rows[] = ['INSCRIÇÃO', '', 'NOME', '', '', '', 'MÊS/ANO', '', ''];

        // Row 5 — values
        $this->rows[] = [$employee->registration_number, '', strtoupper($employee->name), '', '', '', "{$startFmt} a {$endFmt}", '', ''];

        // Row 6 — Lotação | Cargo/Função
        $this->rows[] = ['LOTAÇÃO: '.strtoupper($employee->department), '', '', '', '', '', 'CARGO/FUNÇÃO: '.strtoupper($employee->position), '', ''];

        // Row 7 — blank
        $this->rows[] = ['', '', '', '', '', '', '', '', ''];

        // Row 8 — Group headers: DIA | MANHÃ (span 4) | TARDE (span 4)
        $this->rows[] = ['DIA', 'MANHÃ', '', '', '', 'TARDE', '', '', ''];

        // Row 9 — Sub-group: ENTRADA (2) | SAÍDA (2) | ENTRADA (2) | SAÍDA (2)
        $this->rows[] = ['', 'ENTRADA', '', 'SAÍDA', '', 'ENTRADA', '', 'SAÍDA', ''];

        // Row 10 — Column labels
        $this->rows[] = ['', 'HORA', 'RUBRICA', 'HORA', 'RUBRICA', 'HORA', 'RUBRICA', 'HORA', 'RUBRICA'];

        // Rows 11-41 — Days 1-31
        $daysByNumber = collect($this->reportData['days'])->keyBy('number');

        for ($d = 1; $d <= 31; $d++) {
            $day = $daysByNumber->get($d);

            if (! $day) {
                $this->rows[] = [str_pad((string) $d, 2, '0', STR_PAD_LEFT), '', '', '', '', '', '', '', ''];

                continue;
            }

            $isSpecial = $day['isWeekend'] || $day['holiday'] !== null || $day['note'] !== null;

            if ($isSpecial) {
                $this->rows[] = [str_pad((string) $d, 2, '0', STR_PAD_LEFT), '', '', '', '', '', '', '', ''];

                continue;
            }

            $time = fn (TipoBatida $type): string => isset($day['entries'][$type->value])
                ? Carbon::parse($day['entries'][$type->value]->recorded_at)->format('H:i')
                : '';

            $this->rows[] = [
                str_pad((string) $d, 2, '0', STR_PAD_LEFT),
                $time(TipoBatida::MorningEntry),
                '',
                $time(TipoBatida::MorningExit),
                '',
                $time(TipoBatida::AfternoonEntry),
                '',
                $time(TipoBatida::AfternoonExit),
                '',
            ];
        }

        // Row 42 — Observação header
        $this->rows[] = ['OBSERVAÇÃO', '', '', '', '', '', '', '', ''];

        // Row 43 — Observation content
        $obsLines = collect($this->reportData['days'])
            ->filter(fn ($d) => ! $d['isWeekend'] && ($d['holiday'] !== null || $d['note'] !== null))
            ->map(function ($d) {
                $label = str_pad((string) $d['number'], 2, '0', STR_PAD_LEFT).' — ';
                if ($d['holiday'] !== null) {
                    return $label.'Feriado: '.$d['holiday']->description;
                }
                $note = $d['note']->type->getLabel();
                if ($d['note']->notes) {
                    $note .= ': '.$d['note']->notes;
                }

                return $label.$note;
            })
            ->implode(' | ');

        $this->rows[] = [$obsLines, '', '', '', '', '', '', '', ''];

        // Row 44 — blank
        $this->rows[] = ['', '', '', '', '', '', '', '', ''];

        // Rows 45-46 — Signatures
        $this->rows[] = ['Responsável pela frequência', '', '', '', '', 'Assinatura do Chefe Imediato', '', '', ''];
        $this->rows[] = ['', '', '', '', '', '', '', '', ''];

        return $this->rows;
    }

    /** @return array<string, int|float> */
    public function columnWidths(): array
    {
        return [
            'A' => 8,
            'B' => 10,
            'C' => 16,
            'D' => 10,
            'E' => 16,
            'F' => 10,
            'G' => 16,
            'H' => 10,
            'I' => 16,
        ];
    }

    /** @return array<int, callable> */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $this->applyStyles($event->sheet->getDelegate());
            },
        ];
    }

    private function applyStyles(Worksheet $sheet): void
    {
        $center = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]];
        $bold = ['font' => ['bold' => true]];
        $gray = ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D1D5DB']]];
        $lightGray = ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']]];
        $allBorders = ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]]];
        $outline = ['borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '000000']]]];

        // Row 1 — Title
        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A1')->applyFromArray(array_merge($bold, $center, ['font' => ['bold' => true, 'size' => 13]]));
        $sheet->getRowDimension(1)->setRowHeight(18);

        // Row 2 — Subtitle
        $sheet->mergeCells('A2:I2');
        $sheet->getStyle('A2')->applyFromArray(array_merge($bold, $center, ['font' => ['bold' => true, 'size' => 11]]));
        $sheet->getRowDimension(2)->setRowHeight(16);

        // Row 3 — blank
        $sheet->getRowDimension(3)->setRowHeight(6);

        // Row 4 — info labels (merged cells)
        $sheet->mergeCells('A4:B4');
        $sheet->mergeCells('C4:F4');
        $sheet->mergeCells('G4:I4');
        $sheet->getStyle('A4:I4')->applyFromArray(array_merge($bold, $center, $gray, $allBorders));

        // Row 5 — info values
        $sheet->mergeCells('A5:B5');
        $sheet->mergeCells('C5:F5');
        $sheet->mergeCells('G5:I5');
        $sheet->getStyle('A5:I5')->applyFromArray(array_merge($center, $allBorders));

        // Row 6 — Lotação / Cargo
        $sheet->mergeCells('A6:F6');
        $sheet->mergeCells('G6:I6');
        $sheet->getStyle('A6:I6')->applyFromArray(array_merge($allBorders));
        $sheet->getRowDimension(6)->setRowHeight(14);

        // Row 7 — blank
        $sheet->getRowDimension(7)->setRowHeight(4);

        // Row 8 — group headers: DIA | MANHÃ (B-E) | TARDE (F-I)
        $sheet->mergeCells('B8:E8');
        $sheet->mergeCells('F8:I8');
        $sheet->getStyle('A8:I8')->applyFromArray(array_merge($bold, $center, $gray, $allBorders));
        $sheet->getRowDimension(8)->setRowHeight(13);

        // Row 9 — sub-group: ENTRADA (B-C) | SAÍDA (D-E) | ENTRADA (F-G) | SAÍDA (H-I)
        $sheet->mergeCells('B9:C9');
        $sheet->mergeCells('D9:E9');
        $sheet->mergeCells('F9:G9');
        $sheet->mergeCells('H9:I9');
        $sheet->getStyle('A9:I9')->applyFromArray(array_merge($bold, $center, $lightGray, $allBorders));
        $sheet->getRowDimension(9)->setRowHeight(12);

        // Row 10 — column labels
        $sheet->getStyle('A10:I10')->applyFromArray(array_merge($bold, $center, $lightGray, $allBorders));
        $sheet->getRowDimension(10)->setRowHeight(12);

        // Rows 11-41 — day rows
        for ($r = 11; $r <= 41; $r++) {
            $sheet->getStyle("A{$r}:I{$r}")->applyFromArray(array_merge($center, $allBorders));
            $sheet->getRowDimension($r)->setRowHeight(11);
        }

        // Row 42 — Observação label
        $sheet->mergeCells('A42:I42');
        $sheet->getStyle('A42')->applyFromArray(array_merge($bold, $gray, $allBorders));
        $sheet->getRowDimension(42)->setRowHeight(12);

        // Row 43 — Observação content
        $sheet->mergeCells('A43:I43');
        $sheet->getStyle('A43')->applyFromArray($allBorders);
        $sheet->getRowDimension(43)->setRowHeight(20);

        // Row 44 — blank
        $sheet->getRowDimension(44)->setRowHeight(6);

        // Row 45 — signature labels
        $sheet->mergeCells('A45:E45');
        $sheet->mergeCells('F45:I45');
        $sheet->getStyle('A45:I45')->applyFromArray(array_merge($center, $allBorders));
        $sheet->getRowDimension(45)->setRowHeight(18);

        // Row 46 — blank signature line
        $sheet->mergeCells('A46:E46');
        $sheet->mergeCells('F46:I46');
        $sheet->getStyle('A46:I46')->applyFromArray($allBorders);
        $sheet->getRowDimension(46)->setRowHeight(20);

        // Outer border around the whole form
        $sheet->getStyle('A1:I46')->applyFromArray($outline);

        // Freeze header rows
        $sheet->freezePane('A11');

        // Print settings
        $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT);
        $sheet->getPageSetup()->setFitToPage(true);
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);
    }
}
