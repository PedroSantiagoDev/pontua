<?php

use App\Enums\Shift;
use App\Models\Employee;
use App\Models\TimeEntry;

it('creates a morning time entry', function () {
    $entry = TimeEntry::factory()->morning()->create();

    expect($entry->morning_entry)->not->toBeNull();
    expect($entry->morning_exit)->not->toBeNull();
    expect($entry->afternoon_entry)->toBeNull();
    expect($entry->afternoon_exit)->toBeNull();
});

it('creates an afternoon time entry', function () {
    $entry = TimeEntry::factory()->afternoon()->create();

    expect($entry->morning_entry)->toBeNull();
    expect($entry->morning_exit)->toBeNull();
    expect($entry->afternoon_entry)->not->toBeNull();
    expect($entry->afternoon_exit)->not->toBeNull();
});

it('belongs to an employee', function () {
    $employee = Employee::factory()->create();
    $entry = TimeEntry::factory()->create(['employee_id' => $employee->id]);

    expect($entry->employee->id)->toBe($employee->id);
});

it('casts date to Carbon', function () {
    $entry = TimeEntry::factory()->create(['date' => '2026-02-24']);

    expect($entry->date)->toBeInstanceOf(\Carbon\Carbon::class);
    expect($entry->date->toDateString())->toBe('2026-02-24');
});

it('casts shift_override to Shift enum', function () {
    $entry = TimeEntry::factory()->create(['shift_override' => Shift::Afternoon]);

    expect($entry->shift_override)->toBe(Shift::Afternoon);
});

it('allows null shift_override', function () {
    $entry = TimeEntry::factory()->create(['shift_override' => null]);

    expect($entry->shift_override)->toBeNull();
});

it('enforces unique employee_id and date', function () {
    $employee = Employee::factory()->create();

    TimeEntry::factory()->create([
        'employee_id' => $employee->id,
        'date' => '2026-02-24',
    ]);

    TimeEntry::factory()->create([
        'employee_id' => $employee->id,
        'date' => '2026-02-24',
    ]);
})->throws(\Illuminate\Database\UniqueConstraintViolationException::class);

it('creates entry without any time fields', function () {
    $entry = TimeEntry::factory()->withoutEntries()->create();

    expect($entry->morning_entry)->toBeNull();
    expect($entry->morning_exit)->toBeNull();
    expect($entry->afternoon_entry)->toBeNull();
    expect($entry->afternoon_exit)->toBeNull();
});
