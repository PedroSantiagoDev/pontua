<?php

use App\Enums\HolidayScope;
use App\Enums\HolidayType;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\TimeEntry;
use App\Services\FrequencySheetPdfExporter;
use Illuminate\Support\Carbon;

function preparePdfData(Employee $employee, int $month, int $year): array
{
    $exporter = new FrequencySheetPdfExporter;

    return $exporter->prepareData($employee, $month, $year);
}

// --- Header & Employee Data ---

it('includes employee data in prepared data', function () {
    $employee = Employee::factory()->create([
        'name' => 'Maria Silva',
        'inscription' => '12345/26',
        'department' => 'Diretoria de TI',
        'position' => 'Analista de Sistemas',
    ]);

    $data = preparePdfData($employee, 2, 2026);

    expect($data['employee']->name)->toBe('Maria Silva')
        ->and($data['employee']->inscription)->toBe('12345/26')
        ->and($data['employee']->department)->toBe('Diretoria de TI')
        ->and($data['employee']->position)->toBe('Analista de Sistemas')
        ->and($data['period'])->toBe('01/02/2026 A 28/02/2026')
        ->and($data['monthName'])->toBe('Fevereiro');
});

// --- Time entries ---

it('includes time entry data for present days', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-31'));

    $employee = Employee::factory()->create(['name' => 'João']);

    TimeEntry::factory()->for($employee)->create([
        'date' => '2026-01-05',
        'morning_entry' => '08:00',
        'morning_exit' => '12:00',
        'afternoon_entry' => '14:00',
        'afternoon_exit' => '18:00',
    ]);

    $data = preparePdfData($employee, 1, 2026);

    // Day 5 is index 4 (0-based)
    $day5 = $data['days'][4];
    expect($day5['type'])->toBe('present')
        ->and($day5['morning_entry'])->toBe('08:00')
        ->and($day5['morning_exit'])->toBe('12:00')
        ->and($day5['afternoon_entry'])->toBe('14:00')
        ->and($day5['afternoon_exit'])->toBe('18:00')
        ->and($day5['rubrica'])->toBe('João');
});

// --- FALTA ---

it('marks work days without entries as absent', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-31'));

    $employee = Employee::factory()->create();

    $data = preparePdfData($employee, 1, 2026);

    // Jan 5, 2026 is Monday - no entry should be absent
    $day5 = $data['days'][4];
    expect($day5['type'])->toBe('absent');
});

// --- Weekends ---

it('marks weekends correctly', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-31'));

    $employee = Employee::factory()->create();

    $data = preparePdfData($employee, 1, 2026);

    // Jan 3, 2026 is Saturday (index 2)
    expect($data['days'][2]['type'])->toBe('weekend');

    // Jan 4, 2026 is Sunday (index 3)
    expect($data['days'][3]['type'])->toBe('weekend');
});

// --- Holidays ---

it('marks holidays and adds observation', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-31'));

    $employee = Employee::factory()->create();

    Holiday::factory()->create([
        'date' => '2026-01-05',
        'name' => 'Feriado Teste',
        'type' => HolidayType::Holiday,
        'scope' => HolidayScope::All,
        'recurrent' => false,
    ]);

    $data = preparePdfData($employee, 1, 2026);

    $day5 = $data['days'][4];
    expect($day5['type'])->toBe('holiday');

    expect($data['observations'])->toContain('Dia 5 - Feriado: Feriado Teste');
});

it('marks optional holidays and adds observation', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-31'));

    $employee = Employee::factory()->create();

    Holiday::factory()->optional()->create([
        'date' => '2026-01-06',
        'name' => 'Ponto Facultativo Teste',
        'scope' => HolidayScope::All,
        'recurrent' => false,
    ]);

    $data = preparePdfData($employee, 1, 2026);

    $day6 = $data['days'][5];
    expect($day6['type'])->toBe('optional');

    expect($data['observations'])->toContain('Dia 6 - Ponto Facultativo: Ponto Facultativo Teste');
});

// --- Recurrent holidays ---

it('matches recurrent holidays by month and day', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-31'));

    $employee = Employee::factory()->create();

    Holiday::factory()->create([
        'date' => '2020-01-05',
        'name' => 'Feriado Recorrente',
        'type' => HolidayType::Holiday,
        'scope' => HolidayScope::All,
        'recurrent' => true,
    ]);

    $data = preparePdfData($employee, 1, 2026);

    $day5 = $data['days'][4];
    expect($day5['type'])->toBe('holiday');

    $obsText = implode('; ', $data['observations']);
    expect($obsText)->toContain('Feriado Recorrente');
});

// --- Partial dispensations ---

it('marks dispensed employee row as dispensation with observation', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-31'));

    $employee = Employee::factory()->create();

    $holiday = Holiday::factory()->partial()->create([
        'date' => '2026-01-05',
        'name' => 'Dispensa Administrativa',
        'recurrent' => false,
    ]);

    $holiday->employees()->attach($employee->id, ['reason' => 'Motivo especial']);

    $data = preparePdfData($employee, 1, 2026);

    $day5 = $data['days'][4];
    expect($day5['type'])->toBe('dispensation');

    $obsText = implode('; ', $data['observations']);
    expect($obsText)->toContain('Dispensa')
        ->and($obsText)->toContain('Dispensa Administrativa');
});

it('marks FALTA for non-dispensed employee on partial dispensation day', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-31'));

    $employee = Employee::factory()->create();
    $otherEmployee = Employee::factory()->create();

    $holiday = Holiday::factory()->partial()->create([
        'date' => '2026-01-05',
        'name' => 'Dispensa Administrativa',
        'recurrent' => false,
    ]);

    $holiday->employees()->attach($otherEmployee->id, ['reason' => 'Motivo']);

    $data = preparePdfData($employee, 1, 2026);

    $day5 = $data['days'][4];
    expect($day5['type'])->toBe('absent');
});

// --- Days beyond month length ---

it('marks days beyond month length as blank', function () {
    $employee = Employee::factory()->create();

    $data = preparePdfData($employee, 2, 2026);

    // Feb 2026 has 28 days. Days 29, 30, 31 should be blank
    expect($data['days'][28]['type'])->toBe('blank')
        ->and($data['days'][29]['type'])->toBe('blank')
        ->and($data['days'][30]['type'])->toBe('blank');
});

// --- Future days ---

it('marks future days correctly', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-15'));

    $employee = Employee::factory()->create();

    $data = preparePdfData($employee, 1, 2026);

    // Jan 16, 2026 is Friday (future weekday) = index 15
    expect($data['days'][15]['type'])->toBe('future');
});

// --- Dispensation with scope all ---

it('marks dispensation with scope all correctly', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-31'));

    $employee = Employee::factory()->create();

    Holiday::factory()->create([
        'date' => '2026-01-05',
        'name' => 'Dispensa Geral',
        'type' => HolidayType::Partial,
        'scope' => HolidayScope::All,
        'recurrent' => false,
    ]);

    $data = preparePdfData($employee, 1, 2026);

    $day5 = $data['days'][4];
    expect($day5['type'])->toBe('dispensation');

    $obsText = implode('; ', $data['observations']);
    expect($obsText)->toContain('Dispensa')
        ->and($obsText)->toContain('Dispensa Geral');
});

// --- PDF generation ---

it('generates a PDF streamed response', function () {
    $employee = Employee::factory()->create();

    $exporter = new FrequencySheetPdfExporter;
    $response = $exporter->generate($employee, 1, 2026);

    expect($response)->toBeInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class)
        ->and($response->headers->get('content-type'))->toContain('pdf')
        ->and($response->headers->get('content-disposition'))->toContain("frequencia-{$employee->inscription}-1-2026.pdf");
});

// --- 31 days always present ---

it('always returns 31 day entries', function () {
    $employee = Employee::factory()->create();

    $data = preparePdfData($employee, 2, 2026);

    expect($data['days'])->toHaveCount(31);
});
