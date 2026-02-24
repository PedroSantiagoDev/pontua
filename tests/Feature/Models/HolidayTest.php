<?php

use App\Enums\HolidayScope;
use App\Enums\HolidayType;
use App\Models\Employee;
use App\Models\Holiday;

it('creates a holiday with factory', function () {
    $holiday = Holiday::factory()->create();

    expect($holiday->type)->toBe(HolidayType::Holiday);
    expect($holiday->scope)->toBe(HolidayScope::All);
    expect($holiday->recurrent)->toBeTrue();
});

it('creates an optional holiday', function () {
    $holiday = Holiday::factory()->optional()->create();

    expect($holiday->type)->toBe(HolidayType::Optional);
});

it('creates a partial holiday', function () {
    $holiday = Holiday::factory()->partial()->create();

    expect($holiday->type)->toBe(HolidayType::Partial);
    expect($holiday->scope)->toBe(HolidayScope::Partial);
});

it('casts date to Carbon', function () {
    $holiday = Holiday::factory()->create(['date' => '2026-01-01']);

    expect($holiday->date)->toBeInstanceOf(\Carbon\Carbon::class);
    expect($holiday->date->toDateString())->toBe('2026-01-01');
});

it('casts recurrent to boolean', function () {
    $holiday = Holiday::factory()->create(['recurrent' => 1]);

    expect($holiday->recurrent)->toBeTrue();
});

it('belongs to many employees with pivot reason', function () {
    $holiday = Holiday::factory()->partial()->create();
    $employee1 = Employee::factory()->create();
    $employee2 = Employee::factory()->create();

    $holiday->employees()->attach([
        $employee1->id => ['reason' => 'Motivo 1'],
        $employee2->id => ['reason' => 'Motivo 2'],
    ]);

    expect($holiday->employees)->toHaveCount(2);
    expect($holiday->employees->first()->pivot->reason)->toBe('Motivo 1');
    expect($holiday->employees->last()->pivot->reason)->toBe('Motivo 2');
});

it('casts type to HolidayType enum', function () {
    $holiday = Holiday::factory()->create(['type' => 'optional']);

    expect($holiday->type)->toBe(HolidayType::Optional);
});

it('casts scope to HolidayScope enum', function () {
    $holiday = Holiday::factory()->create(['scope' => 'partial']);

    expect($holiday->scope)->toBe(HolidayScope::Partial);
});
