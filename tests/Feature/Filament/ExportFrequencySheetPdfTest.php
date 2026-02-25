<?php

use App\Filament\Resources\Employees\Pages\ListEmployees;
use App\Models\Employee;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

// --- Access control ---

it('shows PDF export action for admin users', function () {
    $admin = User::factory()->admin()->create();
    $employee = Employee::factory()->create();

    actingAs($admin);

    Livewire::test(ListEmployees::class)
        ->assertActionVisible(TestAction::make('exportPdf')->table($employee));
});

it('shows PDF export action for manager users', function () {
    $manager = User::factory()->manager()->create();
    $employee = Employee::factory()->create();

    actingAs($manager);

    Livewire::test(ListEmployees::class)
        ->assertActionVisible(TestAction::make('exportPdf')->table($employee));
});

it('denies employee users access to PDF export', function () {
    $employeeUser = User::factory()->employee()->create();

    actingAs($employeeUser);

    Livewire::test(ListEmployees::class)
        ->assertForbidden();
});

// --- Action execution ---

it('can call PDF export action with month and year', function () {
    $admin = User::factory()->admin()->create();
    $employee = Employee::factory()->create();

    actingAs($admin);

    Livewire::test(ListEmployees::class)
        ->callAction(TestAction::make('exportPdf')->table($employee), [
            'month' => 1,
            'year' => 2026,
        ]);
});

// --- Validation ---

it('requires month to export PDF', function () {
    $admin = User::factory()->admin()->create();
    $employee = Employee::factory()->create();

    actingAs($admin);

    Livewire::test(ListEmployees::class)
        ->callAction(TestAction::make('exportPdf')->table($employee), [
            'month' => null,
            'year' => 2026,
        ])
        ->assertHasActionErrors(['month' => 'required']);
});

it('requires year to export PDF', function () {
    $admin = User::factory()->admin()->create();
    $employee = Employee::factory()->create();

    actingAs($admin);

    Livewire::test(ListEmployees::class)
        ->callAction(TestAction::make('exportPdf')->table($employee), [
            'month' => 1,
            'year' => null,
        ])
        ->assertHasActionErrors(['year' => 'required']);
});
