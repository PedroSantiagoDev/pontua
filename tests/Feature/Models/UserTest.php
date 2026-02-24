<?php

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\User;

it('creates a user with default employee role', function () {
    $user = User::factory()->create();

    expect($user->role)->toBe(UserRole::Employee);
});

it('creates an admin user', function () {
    $user = User::factory()->admin()->create();

    expect($user->role)->toBe(UserRole::Admin);
    expect($user->isAdmin())->toBeTrue();
    expect($user->isManager())->toBeFalse();
    expect($user->isEmployee())->toBeFalse();
});

it('creates a manager user', function () {
    $user = User::factory()->manager()->create();

    expect($user->role)->toBe(UserRole::Manager);
    expect($user->isManager())->toBeTrue();
});

it('has an employee relationship', function () {
    $employee = Employee::factory()->create();

    expect($employee->user->employee->id)->toBe($employee->id);
});

it('casts role to UserRole enum', function () {
    $user = User::factory()->create(['role' => 'admin']);

    expect($user->role)->toBeInstanceOf(UserRole::class);
    expect($user->role)->toBe(UserRole::Admin);
});
