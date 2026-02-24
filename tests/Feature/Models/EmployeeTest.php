<?php

use App\Enums\Shift;
use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\TimeEntry;
use App\Models\User;

it('creates an employee with factory', function () {
    $employee = Employee::factory()->create();

    expect($employee)->toBeInstanceOf(Employee::class);
    expect($employee->name)->not->toBeEmpty();
    expect($employee->inscription)->not->toBeEmpty();
    expect($employee->department)->not->toBeEmpty();
    expect($employee->position)->not->toBeEmpty();
    expect($employee->organization)->not->toBeEmpty();
    expect($employee->default_shift)->toBeInstanceOf(Shift::class);
});

it('enforces unique inscription', function () {
    Employee::factory()->create(['inscription' => '123456']);

    Employee::factory()->create(['inscription' => '123456']);
})->throws(\Illuminate\Database\UniqueConstraintViolationException::class);

it('belongs to a user', function () {
    $user = User::factory()->employee()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    expect($employee->user->id)->toBe($user->id);
    expect($employee->user->role)->toBe(UserRole::Employee);
});

it('has many time entries', function () {
    $employee = Employee::factory()->create();
    TimeEntry::factory()->count(3)->create([
        'employee_id' => $employee->id,
        'date' => fn () => fake()->unique()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
    ]);

    expect($employee->timeEntries)->toHaveCount(3);
});

it('belongs to many holidays with pivot reason', function () {
    $employee = Employee::factory()->create();
    $holiday = Holiday::factory()->partial()->create();

    $holiday->employees()->attach($employee->id, ['reason' => 'Convocação para evento']);

    expect($employee->holidays)->toHaveCount(1);
    expect($employee->holidays->first()->pivot->reason)->toBe('Convocação para evento');
});

it('casts default_shift to Shift enum', function () {
    $employee = Employee::factory()->morning()->create();

    expect($employee->default_shift)->toBe(Shift::Morning);
});

it('creates employee with user that has employee role', function () {
    $employee = Employee::factory()->create();

    expect($employee->user->role)->toBe(UserRole::Employee);
});
