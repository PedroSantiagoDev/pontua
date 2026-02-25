<?php

use App\Filament\Resources\Employees\Pages\ListEmployees;
use App\Models\Employee;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

// ============================================================
// Batch Excel Export — Access Control
// ============================================================

it('shows batch Excel export action for admin users', function () {
    actingAs(User::factory()->admin()->create());

    Livewire::test(ListEmployees::class)
        ->assertActionVisible('batchExportExcel');
});

it('shows batch Excel export action for manager users', function () {
    actingAs(User::factory()->manager()->create());

    Livewire::test(ListEmployees::class)
        ->assertActionVisible('batchExportExcel');
});

it('hides batch Excel export action for employee users', function () {
    actingAs(User::factory()->employee()->create());

    Livewire::test(ListEmployees::class)
        ->assertForbidden();
});

// ============================================================
// Batch Excel Export — Validation
// ============================================================

it('requires month to batch export Excel', function () {
    actingAs(User::factory()->admin()->create());

    Livewire::test(ListEmployees::class)
        ->callAction('batchExportExcel', [
            'month' => null,
            'year' => 2026,
        ])
        ->assertHasActionErrors(['month' => 'required']);
});

it('requires year to batch export Excel', function () {
    actingAs(User::factory()->admin()->create());

    Livewire::test(ListEmployees::class)
        ->callAction('batchExportExcel', [
            'month' => 1,
            'year' => null,
        ])
        ->assertHasActionErrors(['year' => 'required']);
});

// ============================================================
// Batch Excel Export — Execution
// ============================================================

it('can call batch Excel export action with month and year', function () {
    actingAs(User::factory()->admin()->create());

    Employee::factory()->count(3)->create();

    Livewire::test(ListEmployees::class)
        ->callAction('batchExportExcel', [
            'month' => 1,
            'year' => 2026,
        ]);
});

// ============================================================
// Batch PDF Export — Access Control
// ============================================================

it('shows batch PDF export action for admin users', function () {
    actingAs(User::factory()->admin()->create());

    Livewire::test(ListEmployees::class)
        ->assertActionVisible('batchExportPdf');
});

it('shows batch PDF export action for manager users', function () {
    actingAs(User::factory()->manager()->create());

    Livewire::test(ListEmployees::class)
        ->assertActionVisible('batchExportPdf');
});

it('hides batch PDF export action for employee users', function () {
    actingAs(User::factory()->employee()->create());

    Livewire::test(ListEmployees::class)
        ->assertForbidden();
});

// ============================================================
// Batch PDF Export — Validation
// ============================================================

it('requires month to batch export PDF', function () {
    actingAs(User::factory()->admin()->create());

    Livewire::test(ListEmployees::class)
        ->callAction('batchExportPdf', [
            'month' => null,
            'year' => 2026,
        ])
        ->assertHasActionErrors(['month' => 'required']);
});

it('requires year to batch export PDF', function () {
    actingAs(User::factory()->admin()->create());

    Livewire::test(ListEmployees::class)
        ->callAction('batchExportPdf', [
            'month' => 1,
            'year' => null,
        ])
        ->assertHasActionErrors(['year' => 'required']);
});

// ============================================================
// Batch PDF Export — Execution
// ============================================================

it('can call batch PDF export action with month and year', function () {
    actingAs(User::factory()->admin()->create());

    Employee::factory()->count(3)->create();

    Livewire::test(ListEmployees::class)
        ->callAction('batchExportPdf', [
            'month' => 1,
            'year' => 2026,
        ]);
});

// ============================================================
// Service-level batch tests
// ============================================================

it('generates Excel spreadsheet with one sheet per employee', function () {
    $employees = Employee::factory()->count(3)->create();

    $exporter = new App\Services\FrequencySheetExporter;
    $spreadsheet = $exporter->generateBatch($employees, 1, 2026);

    expect($spreadsheet->getSheetCount())->toBe(3);

    foreach ($employees as $index => $employee) {
        $sheet = $spreadsheet->getSheet($index);
        expect($sheet->getTitle())->toBe(mb_substr($employee->name, 0, 31));
        expect($sheet->getCell('C10')->getValue())->toBe($employee->name);
        expect((string) $sheet->getCell('A10')->getValue())->toBe($employee->inscription);
    }
});

it('generates batch PDF with data for all employees', function () {
    $employees = Employee::factory()->count(2)->create();

    $exporter = new App\Services\FrequencySheetPdfExporter;
    $response = $exporter->generateBatch($employees, 2, 2026);

    expect($response)->toBeInstanceOf(Symfony\Component\HttpFoundation\StreamedResponse::class)
        ->and($response->headers->get('Content-Type'))->toBe('application/pdf');
});
