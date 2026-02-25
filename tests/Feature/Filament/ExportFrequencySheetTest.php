<?php

use App\Filament\Resources\Employees\Pages\ListEmployees;
use App\Models\Employee;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

// --- Access control ---

it('shows export action for admin users', function () {
    $admin = User::factory()->admin()->create();
    $employee = Employee::factory()->create();

    actingAs($admin);

    Livewire::test(ListEmployees::class)
        ->assertActionVisible(TestAction::make('exportExcel')->table($employee));
});

it('shows export action for manager users', function () {
    $manager = User::factory()->manager()->create();
    $employee = Employee::factory()->create();

    actingAs($manager);

    Livewire::test(ListEmployees::class)
        ->assertActionVisible(TestAction::make('exportExcel')->table($employee));
});

it('denies employee users access to employees page', function () {
    $employeeUser = User::factory()->employee()->create();

    actingAs($employeeUser);

    Livewire::test(ListEmployees::class)
        ->assertForbidden();
});

// --- Action execution ---

it('can call export action with month and year', function () {
    $admin = User::factory()->admin()->create();
    $employee = Employee::factory()->create();

    actingAs($admin);

    Livewire::test(ListEmployees::class)
        ->callAction(TestAction::make('exportExcel')->table($employee), [
            'month' => 1,
            'year' => 2026,
        ]);
});

// --- Validation ---

it('requires month to export', function () {
    $admin = User::factory()->admin()->create();
    $employee = Employee::factory()->create();

    actingAs($admin);

    Livewire::test(ListEmployees::class)
        ->callAction(TestAction::make('exportExcel')->table($employee), [
            'month' => null,
            'year' => 2026,
        ])
        ->assertHasActionErrors(['month' => 'required']);
});

it('requires year to export', function () {
    $admin = User::factory()->admin()->create();
    $employee = Employee::factory()->create();

    actingAs($admin);

    Livewire::test(ListEmployees::class)
        ->callAction(TestAction::make('exportExcel')->table($employee), [
            'month' => 1,
            'year' => null,
        ])
        ->assertHasActionErrors(['year' => 'required']);
});
