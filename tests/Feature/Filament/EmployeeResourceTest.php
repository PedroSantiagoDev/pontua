<?php

use App\Enums\Shift;
use App\Enums\UserRole;
use App\Filament\Resources\Employees\Pages\CreateEmployee;
use App\Filament\Resources\Employees\Pages\EditEmployee;
use App\Filament\Resources\Employees\Pages\ListEmployees;
use App\Models\Employee;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

// --- Access Control ---

it('allows admin to access the employees list page', function () {
    actingAs(User::factory()->admin()->create());

    Livewire::test(ListEmployees::class)
        ->assertOk();
});

it('allows manager to access the employees list page', function () {
    actingAs(User::factory()->manager()->create());

    Livewire::test(ListEmployees::class)
        ->assertOk();
});

it('prevents employee from accessing the employees list page', function () {
    actingAs(User::factory()->employee()->create());

    Livewire::test(ListEmployees::class)
        ->assertForbidden();
});

// --- Listing ---

it('displays employees in the table', function () {
    actingAs(User::factory()->admin()->create());

    $employees = Employee::factory()->count(3)->create();

    Livewire::test(ListEmployees::class)
        ->assertCanSeeTableRecords($employees);
});

it('can search employees by name', function () {
    actingAs(User::factory()->admin()->create());

    $employees = Employee::factory()->count(5)->create();

    Livewire::test(ListEmployees::class)
        ->searchTable($employees->first()->name)
        ->assertCanSeeTableRecords($employees->where('name', $employees->first()->name))
        ->assertCanNotSeeTableRecords($employees->where('name', '!=', $employees->first()->name));
});

it('can search employees by inscription', function () {
    actingAs(User::factory()->admin()->create());

    $employees = Employee::factory()->count(5)->create();

    Livewire::test(ListEmployees::class)
        ->searchTable($employees->first()->inscription)
        ->assertCanSeeTableRecords($employees->where('inscription', $employees->first()->inscription));
});

// --- Create ---

it('allows admin to access the create page', function () {
    actingAs(User::factory()->admin()->create());

    Livewire::test(CreateEmployee::class)
        ->assertOk();
});

it('allows manager to access the create page', function () {
    actingAs(User::factory()->manager()->create());

    Livewire::test(CreateEmployee::class)
        ->assertOk();
});

it('can create an employee and auto-creates a linked user', function () {
    actingAs(User::factory()->admin()->create());

    Livewire::test(CreateEmployee::class)
        ->fillForm([
            'name' => 'João Silva',
            'inscription' => '123456',
            'department' => 'TI',
            'position' => 'Analista',
            'organization' => 'AGED-MA',
            'default_shift' => Shift::Morning->value,
            'email' => 'joao@example.com',
            'password' => 'password123',
        ])
        ->call('create')
        ->assertNotified()
        ->assertRedirect();

    assertDatabaseHas(Employee::class, [
        'name' => 'João Silva',
        'inscription' => '123456',
        'department' => 'TI',
        'position' => 'Analista',
        'organization' => 'AGED-MA',
        'default_shift' => Shift::Morning->value,
    ]);

    assertDatabaseHas(User::class, [
        'name' => 'João Silva',
        'email' => 'joao@example.com',
        'role' => UserRole::Employee->value,
    ]);

    $employee = Employee::where('inscription', '123456')->first();
    expect($employee->user)->not->toBeNull()
        ->and($employee->user->email)->toBe('joao@example.com')
        ->and($employee->user->role)->toBe(UserRole::Employee);
});

it('validates required fields on create', function (array $data, array $errors) {
    actingAs(User::factory()->admin()->create());

    Livewire::test(CreateEmployee::class)
        ->fillForm([
            'name' => 'Test',
            'inscription' => '999999',
            'department' => 'TI',
            'position' => 'Analista',
            'organization' => 'AGED-MA',
            'default_shift' => Shift::Morning->value,
            'email' => 'test@example.com',
            'password' => 'password123',
            ...$data,
        ])
        ->call('create')
        ->assertHasFormErrors($errors)
        ->assertNotNotified()
        ->assertNoRedirect();
})->with([
    '`name` is required' => [['name' => null], ['name' => 'required']],
    '`inscription` is required' => [['inscription' => null], ['inscription' => 'required']],
    '`department` is required' => [['department' => null], ['department' => 'required']],
    '`position` is required' => [['position' => null], ['position' => 'required']],
    '`organization` is required' => [['organization' => null], ['organization' => 'required']],
    '`default_shift` is required' => [['default_shift' => null], ['default_shift' => 'required']],
    '`email` is required' => [['email' => null], ['email' => 'required']],
    '`email` must be valid' => [['email' => 'invalid'], ['email' => 'email']],
    '`password` is required' => [['password' => null], ['password' => 'required']],
]);

it('validates unique inscription on create', function () {
    actingAs(User::factory()->admin()->create());

    $existing = Employee::factory()->create(['inscription' => '111111']);

    Livewire::test(CreateEmployee::class)
        ->fillForm([
            'name' => 'Test',
            'inscription' => '111111',
            'department' => 'TI',
            'position' => 'Analista',
            'organization' => 'AGED-MA',
            'default_shift' => Shift::Morning->value,
            'email' => 'new@example.com',
            'password' => 'password123',
        ])
        ->call('create')
        ->assertHasFormErrors(['inscription' => 'unique']);
});

it('validates unique email on create', function () {
    actingAs(User::factory()->admin()->create());

    $existingUser = User::factory()->create(['email' => 'taken@example.com']);

    Livewire::test(CreateEmployee::class)
        ->fillForm([
            'name' => 'Test',
            'inscription' => '222222',
            'department' => 'TI',
            'position' => 'Analista',
            'organization' => 'AGED-MA',
            'default_shift' => Shift::Morning->value,
            'email' => 'taken@example.com',
            'password' => 'password123',
        ])
        ->call('create')
        ->assertHasFormErrors(['email' => 'unique']);
});

// --- Edit ---

it('allows admin to access the edit page', function () {
    actingAs(User::factory()->admin()->create());

    $employee = Employee::factory()->create();

    Livewire::test(EditEmployee::class, ['record' => $employee->id])
        ->assertOk();
});

it('allows manager to access the edit page', function () {
    actingAs(User::factory()->manager()->create());

    $employee = Employee::factory()->create();

    Livewire::test(EditEmployee::class, ['record' => $employee->id])
        ->assertOk();
});

it('can update an employee', function () {
    actingAs(User::factory()->admin()->create());

    $employee = Employee::factory()->create();

    Livewire::test(EditEmployee::class, ['record' => $employee->id])
        ->fillForm([
            'name' => 'Nome Atualizado',
            'inscription' => $employee->inscription,
            'department' => 'RH',
            'position' => 'Coordenador',
            'organization' => 'AGED-MA',
            'default_shift' => Shift::Afternoon->value,
        ])
        ->call('save')
        ->assertNotified();

    $employee->refresh();

    expect($employee->name)->toBe('Nome Atualizado')
        ->and($employee->department)->toBe('RH')
        ->and($employee->position)->toBe('Coordenador')
        ->and($employee->default_shift)->toBe(Shift::Afternoon);
});

it('validates unique inscription on edit', function () {
    actingAs(User::factory()->admin()->create());

    $employee1 = Employee::factory()->create(['inscription' => 'AAA111']);
    $employee2 = Employee::factory()->create(['inscription' => 'BBB222']);

    Livewire::test(EditEmployee::class, ['record' => $employee2->id])
        ->fillForm([
            'inscription' => 'AAA111',
        ])
        ->call('save')
        ->assertHasFormErrors(['inscription' => 'unique']);
});

it('allows keeping the same inscription on edit', function () {
    actingAs(User::factory()->admin()->create());

    $employee = Employee::factory()->create(['inscription' => 'KEEP01']);

    Livewire::test(EditEmployee::class, ['record' => $employee->id])
        ->fillForm([
            'name' => 'Updated Name',
            'inscription' => 'KEEP01',
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();
});

// --- Delete ---

it('can delete an employee', function () {
    actingAs(User::factory()->admin()->create());

    $employee = Employee::factory()->create();

    Livewire::test(EditEmployee::class, ['record' => $employee->id])
        ->callAction('delete')
        ->assertNotified()
        ->assertRedirect();

    expect(Employee::find($employee->id))->toBeNull();
});
