<?php

use App\Enums\HolidayScope;
use App\Enums\HolidayType;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\TimeEntry;
use App\Services\FrequencySheetExporter;
use Illuminate\Support\Carbon;

function generateSheet(Employee $employee, int $month, int $year): \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
{
    $exporter = new FrequencySheetExporter;
    $spreadsheet = $exporter->generate($employee, $month, $year);

    return $spreadsheet->getActiveSheet();
}

// --- Header & Employee Data ---

it('writes institutional headers correctly', function () {
    $employee = Employee::factory()->create();

    $sheet = generateSheet($employee, 1, 2026);

    expect($sheet->getCell('A4')->getValue())->toBe('ESTADO DO MARANHÃO')
        ->and($sheet->getCell('A5')->getValue())->toBe('AGÊNCIA ESTADUAL DE DEFESA AGROPECUÁRIA DO MARANHÃO – AGED-MA')
        ->and($sheet->getCell('A7')->getValue())->toBe('FOLHA INDIVIDUAL DE FREQÜÊNCIA');
});

it('writes employee data in correct cells', function () {
    $employee = Employee::factory()->create([
        'name' => 'Maria Silva',
        'inscription' => '12345/26',
        'department' => 'Diretoria de TI',
        'position' => 'Analista de Sistemas',
    ]);

    $sheet = generateSheet($employee, 2, 2026);

    expect($sheet->getCell('A10')->getValue())->toBe('12345/26')
        ->and($sheet->getCell('C10')->getValue())->toBe('Maria Silva')
        ->and($sheet->getCell('G10')->getValue())->toBe('01/02/2026 A 28/02/2026')
        ->and($sheet->getCell('C12')->getValue())->toBe('Diretoria de TI')
        ->and($sheet->getCell('G12')->getValue())->toBe('Analista de Sistemas');
});

// --- Time entries ---

it('fills time entry data in correct cells', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-31'));

    $employee = Employee::factory()->create(['name' => 'João']);

    TimeEntry::factory()->for($employee)->create([
        'date' => '2026-01-05',
        'morning_entry' => '08:00',
        'morning_exit' => '12:00',
        'afternoon_entry' => '14:00',
        'afternoon_exit' => '18:00',
    ]);

    $sheet = generateSheet($employee, 1, 2026);

    // Day 5 = row 21 (17 + 5 - 1)
    expect($sheet->getCell('B21')->getValue())->toBe('08:00')
        ->and($sheet->getCell('C21')->getValue())->toBe('João')
        ->and($sheet->getCell('D21')->getValue())->toBe('12:00')
        ->and($sheet->getCell('E21')->getValue())->toBe('João')
        ->and($sheet->getCell('F21')->getValue())->toBe('14:00')
        ->and($sheet->getCell('G21')->getValue())->toBe('João')
        ->and($sheet->getCell('H21')->getValue())->toBe('18:00')
        ->and($sheet->getCell('I21')->getValue())->toBe('João');
});

// --- FALTA ---

it('marks work days without entries as FALTA', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-31'));

    $employee = Employee::factory()->create();

    $sheet = generateSheet($employee, 1, 2026);

    // Jan 5, 2026 is Monday - no entry should be FALTA
    // Day 5 = row 21
    expect($sheet->getCell('B21')->getValue())->toBe('FALTA')
        ->and($sheet->getCell('C21')->getValue())->toBe('FALTA')
        ->and($sheet->getCell('D21')->getValue())->toBe('FALTA')
        ->and($sheet->getCell('E21')->getValue())->toBe('FALTA')
        ->and($sheet->getCell('F21')->getValue())->toBe('FALTA')
        ->and($sheet->getCell('G21')->getValue())->toBe('FALTA')
        ->and($sheet->getCell('H21')->getValue())->toBe('FALTA')
        ->and($sheet->getCell('I21')->getValue())->toBe('FALTA');
});

// --- Weekends ---

it('leaves weekend rows blank', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-31'));

    $employee = Employee::factory()->create();

    $sheet = generateSheet($employee, 1, 2026);

    // Jan 3, 2026 is Saturday = row 19
    expect($sheet->getCell('B19')->getValue())->toBeNull()
        ->and($sheet->getCell('C19')->getValue())->toBeNull();

    // Jan 4, 2026 is Sunday = row 20
    expect($sheet->getCell('B20')->getValue())->toBeNull()
        ->and($sheet->getCell('C20')->getValue())->toBeNull();
});

// --- Holidays ---

it('leaves holiday rows blank and adds observation', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-31'));

    $employee = Employee::factory()->create();

    Holiday::factory()->create([
        'date' => '2026-01-05',
        'name' => 'Feriado Teste',
        'type' => HolidayType::Holiday,
        'scope' => HolidayScope::All,
        'recurrent' => false,
    ]);

    $sheet = generateSheet($employee, 1, 2026);

    // Day 5 = row 21 should be blank
    expect($sheet->getCell('B21')->getValue())->toBeNull()
        ->and($sheet->getCell('C21')->getValue())->toBeNull();

    // Observation row (row 48) should have content in row 49
    $obs = $sheet->getCell('A49')->getValue();
    expect($obs)->toContain('Dia 5')
        ->and($obs)->toContain('Feriado')
        ->and($obs)->toContain('Feriado Teste');
});

it('leaves optional holiday rows blank and adds observation', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-31'));

    $employee = Employee::factory()->create();

    Holiday::factory()->optional()->create([
        'date' => '2026-01-06',
        'name' => 'Ponto Facultativo Teste',
        'scope' => HolidayScope::All,
        'recurrent' => false,
    ]);

    $sheet = generateSheet($employee, 1, 2026);

    // Day 6 = row 22 should be blank
    expect($sheet->getCell('B22')->getValue())->toBeNull();

    $obs = $sheet->getCell('A49')->getValue();
    expect($obs)->toContain('Dia 6')
        ->and($obs)->toContain('Ponto Facultativo')
        ->and($obs)->toContain('Ponto Facultativo Teste');
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

    $sheet = generateSheet($employee, 1, 2026);

    // Day 5 = row 21 should be blank (holiday)
    expect($sheet->getCell('B21')->getValue())->toBeNull();

    $obs = $sheet->getCell('A49')->getValue();
    expect($obs)->toContain('Feriado Recorrente');
});

// --- Partial dispensations ---

it('leaves row blank for dispensed employee and adds observation', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-31'));

    $employee = Employee::factory()->create();

    $holiday = Holiday::factory()->partial()->create([
        'date' => '2026-01-05',
        'name' => 'Dispensa Administrativa',
        'recurrent' => false,
    ]);

    $holiday->employees()->attach($employee->id, ['reason' => 'Motivo especial']);

    $sheet = generateSheet($employee, 1, 2026);

    // Day 5 = row 21 should be blank
    expect($sheet->getCell('B21')->getValue())->toBeNull();

    $obs = $sheet->getCell('A49')->getValue();
    expect($obs)->toContain('Dispensa')
        ->and($obs)->toContain('Dispensa Administrativa');
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

    $sheet = generateSheet($employee, 1, 2026);

    // Day 5 = row 21 should be FALTA for the non-dispensed employee
    expect($sheet->getCell('B21')->getValue())->toBe('FALTA');
});

// --- Days beyond month length ---

it('leaves rows blank for days beyond month length', function () {
    $employee = Employee::factory()->create();

    $sheet = generateSheet($employee, 2, 2026);

    // Feb 2026 has 28 days. Day 29 = row 45, Day 30 = row 46, Day 31 = row 47
    expect($sheet->getCell('B45')->getValue())->toBeNull()
        ->and($sheet->getCell('B46')->getValue())->toBeNull()
        ->and($sheet->getCell('B47')->getValue())->toBeNull();
});

// --- Future days ---

it('leaves future day rows blank', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-15'));

    $employee = Employee::factory()->create();

    $sheet = generateSheet($employee, 1, 2026);

    // Jan 16, 2026 is Friday (future weekday) = Day 16 = row 32
    expect($sheet->getCell('B32')->getValue())->toBeNull();
});

// --- Dispensation with scope all ---

it('leaves row blank for dispensation with scope all', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-31'));

    $employee = Employee::factory()->create();

    Holiday::factory()->create([
        'date' => '2026-01-05',
        'name' => 'Dispensa Geral',
        'type' => HolidayType::Partial,
        'scope' => HolidayScope::All,
        'recurrent' => false,
    ]);

    $sheet = generateSheet($employee, 1, 2026);

    // Day 5 = row 21 should be blank
    expect($sheet->getCell('B21')->getValue())->toBeNull();

    $obs = $sheet->getCell('A49')->getValue();
    expect($obs)->toContain('Dispensa')
        ->and($obs)->toContain('Dispensa Geral');
});
