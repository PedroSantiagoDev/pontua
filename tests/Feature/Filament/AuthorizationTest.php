<?php

use App\Filament\Pages\MyTimeEntries;
use App\Filament\Resources\Employees\Pages\CreateEmployee;
use App\Filament\Resources\Employees\Pages\ListEmployees;
use App\Filament\Resources\Holidays\Pages\CreateHoliday;
use App\Filament\Resources\Holidays\Pages\ListHolidays;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Widgets\ClockInWidget;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\TimeEntry;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

// ============================================================
// TimeEntryPolicy
// ============================================================

it('allows admin to view any time entries via policy', function () {
    $admin = User::factory()->admin()->create();

    actingAs($admin);

    expect($admin->can('viewAny', TimeEntry::class))->toBeTrue();
});

it('allows manager to view any time entries via policy', function () {
    $manager = User::factory()->manager()->create();

    actingAs($manager);

    expect($manager->can('viewAny', TimeEntry::class))->toBeTrue();
});

it('allows employee to view any time entries via policy', function () {
    $employee = Employee::factory()->create();

    actingAs($employee->user);

    expect($employee->user->can('viewAny', TimeEntry::class))->toBeTrue();
});

it('allows admin to view a specific time entry', function () {
    $admin = User::factory()->admin()->create();
    $entry = TimeEntry::factory()->create();

    actingAs($admin);

    expect($admin->can('view', $entry))->toBeTrue();
});

it('allows manager to view a specific time entry', function () {
    $manager = User::factory()->manager()->create();
    $entry = TimeEntry::factory()->create();

    actingAs($manager);

    expect($manager->can('view', $entry))->toBeTrue();
});

it('allows employee to view their own time entry', function () {
    $employee = Employee::factory()->create();
    $entry = TimeEntry::factory()->for($employee)->create();

    actingAs($employee->user);

    expect($employee->user->can('view', $entry))->toBeTrue();
});

it('prevents employee from viewing another employees time entry', function () {
    $employee = Employee::factory()->create();
    $otherEmployee = Employee::factory()->create();
    $entry = TimeEntry::factory()->for($otherEmployee)->create();

    actingAs($employee->user);

    expect($employee->user->can('view', $entry))->toBeFalse();
});

it('allows employee to create time entries', function () {
    $employee = Employee::factory()->create();

    actingAs($employee->user);

    expect($employee->user->can('create', TimeEntry::class))->toBeTrue();
});

it('prevents admin from creating time entries', function () {
    $admin = User::factory()->admin()->create();

    actingAs($admin);

    expect($admin->can('create', TimeEntry::class))->toBeFalse();
});

it('prevents manager from creating time entries', function () {
    $manager = User::factory()->manager()->create();

    actingAs($manager);

    expect($manager->can('create', TimeEntry::class))->toBeFalse();
});

it('allows employee to update their own time entry', function () {
    $employee = Employee::factory()->create();
    $entry = TimeEntry::factory()->for($employee)->create();

    actingAs($employee->user);

    expect($employee->user->can('update', $entry))->toBeTrue();
});

it('prevents employee from updating another employees time entry', function () {
    $employee = Employee::factory()->create();
    $otherEmployee = Employee::factory()->create();
    $entry = TimeEntry::factory()->for($otherEmployee)->create();

    actingAs($employee->user);

    expect($employee->user->can('update', $entry))->toBeFalse();
});

it('allows admin to delete a time entry', function () {
    $admin = User::factory()->admin()->create();
    $entry = TimeEntry::factory()->create();

    actingAs($admin);

    expect($admin->can('delete', $entry))->toBeTrue();
});

it('allows manager to delete a time entry', function () {
    $manager = User::factory()->manager()->create();
    $entry = TimeEntry::factory()->create();

    actingAs($manager);

    expect($manager->can('delete', $entry))->toBeTrue();
});

it('prevents employee from deleting a time entry', function () {
    $employee = Employee::factory()->create();
    $entry = TimeEntry::factory()->for($employee)->create();

    actingAs($employee->user);

    expect($employee->user->can('delete', $entry))->toBeFalse();
});

// ============================================================
// UserPolicy — Admin-only resource
// ============================================================

it('allows admin full access to user management', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->create();

    actingAs($admin);

    expect($admin->can('viewAny', User::class))->toBeTrue()
        ->and($admin->can('view', $target))->toBeTrue()
        ->and($admin->can('create', User::class))->toBeTrue()
        ->and($admin->can('update', $target))->toBeTrue()
        ->and($admin->can('delete', $target))->toBeTrue();
});

it('prevents manager from accessing user management', function () {
    $manager = User::factory()->manager()->create();

    actingAs($manager);

    expect($manager->can('viewAny', User::class))->toBeFalse()
        ->and($manager->can('create', User::class))->toBeFalse();

    Livewire::test(ListUsers::class)->assertForbidden();
    Livewire::test(CreateUser::class)->assertForbidden();
});

it('prevents employee from accessing user management', function () {
    $employee = User::factory()->employee()->create();

    actingAs($employee);

    expect($employee->can('viewAny', User::class))->toBeFalse()
        ->and($employee->can('create', User::class))->toBeFalse();

    Livewire::test(ListUsers::class)->assertForbidden();
    Livewire::test(CreateUser::class)->assertForbidden();
});

// ============================================================
// EmployeePolicy — Admin and Manager
// ============================================================

it('allows admin full access to employee management', function () {
    $admin = User::factory()->admin()->create();
    $emp = Employee::factory()->create();

    actingAs($admin);

    expect($admin->can('viewAny', Employee::class))->toBeTrue()
        ->and($admin->can('view', $emp))->toBeTrue()
        ->and($admin->can('create', Employee::class))->toBeTrue()
        ->and($admin->can('update', $emp))->toBeTrue()
        ->and($admin->can('delete', $emp))->toBeTrue();
});

it('allows manager full access to employee management', function () {
    $manager = User::factory()->manager()->create();
    $emp = Employee::factory()->create();

    actingAs($manager);

    expect($manager->can('viewAny', Employee::class))->toBeTrue()
        ->and($manager->can('view', $emp))->toBeTrue()
        ->and($manager->can('create', Employee::class))->toBeTrue()
        ->and($manager->can('update', $emp))->toBeTrue()
        ->and($manager->can('delete', $emp))->toBeTrue();
});

it('prevents employee from accessing employee management', function () {
    $employee = User::factory()->employee()->create();

    actingAs($employee);

    expect($employee->can('viewAny', Employee::class))->toBeFalse()
        ->and($employee->can('create', Employee::class))->toBeFalse();

    Livewire::test(ListEmployees::class)->assertForbidden();
    Livewire::test(CreateEmployee::class)->assertForbidden();
});

// ============================================================
// HolidayPolicy — Admin and Manager
// ============================================================

it('allows admin full access to holiday management', function () {
    $admin = User::factory()->admin()->create();
    $holiday = Holiday::factory()->create();

    actingAs($admin);

    expect($admin->can('viewAny', Holiday::class))->toBeTrue()
        ->and($admin->can('view', $holiday))->toBeTrue()
        ->and($admin->can('create', Holiday::class))->toBeTrue()
        ->and($admin->can('update', $holiday))->toBeTrue()
        ->and($admin->can('delete', $holiday))->toBeTrue();
});

it('allows manager full access to holiday management', function () {
    $manager = User::factory()->manager()->create();
    $holiday = Holiday::factory()->create();

    actingAs($manager);

    expect($manager->can('viewAny', Holiday::class))->toBeTrue()
        ->and($manager->can('view', $holiday))->toBeTrue()
        ->and($manager->can('create', Holiday::class))->toBeTrue()
        ->and($manager->can('update', $holiday))->toBeTrue()
        ->and($manager->can('delete', $holiday))->toBeTrue();
});

it('prevents employee from accessing holiday management', function () {
    $employee = User::factory()->employee()->create();

    actingAs($employee);

    expect($employee->can('viewAny', Holiday::class))->toBeFalse()
        ->and($employee->can('create', Holiday::class))->toBeFalse();

    Livewire::test(ListHolidays::class)->assertForbidden();
    Livewire::test(CreateHoliday::class)->assertForbidden();
});

// ============================================================
// Employee-only features — ClockInWidget and MyTimeEntries
// ============================================================

it('grants employee access to ClockInWidget and MyTimeEntries', function () {
    $employee = Employee::factory()->create();

    actingAs($employee->user);

    expect(ClockInWidget::canView())->toBeTrue();

    Livewire::test(MyTimeEntries::class)->assertOk();
});

it('denies admin access to ClockInWidget and MyTimeEntries', function () {
    $admin = User::factory()->admin()->create();

    actingAs($admin);

    expect(ClockInWidget::canView())->toBeFalse();

    Livewire::test(MyTimeEntries::class)->assertForbidden();
});

it('denies manager access to ClockInWidget and MyTimeEntries', function () {
    $manager = User::factory()->manager()->create();

    actingAs($manager);

    expect(ClockInWidget::canView())->toBeFalse();

    Livewire::test(MyTimeEntries::class)->assertForbidden();
});

// ============================================================
// Admin self-delete protection
// ============================================================

it('prevents admin from deleting themselves via policy', function () {
    $admin = User::factory()->admin()->create();

    actingAs($admin);

    expect($admin->can('delete', $admin))->toBeFalse();
});
