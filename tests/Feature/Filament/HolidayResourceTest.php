<?php

use App\Enums\HolidayScope;
use App\Enums\HolidayType;
use App\Filament\Resources\Holidays\Pages\CreateHoliday;
use App\Filament\Resources\Holidays\Pages\EditHoliday;
use App\Filament\Resources\Holidays\Pages\ListHolidays;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

// --- Access Control ---

it('allows admin to access the holidays list page', function () {
    actingAs(User::factory()->admin()->create());

    Livewire::test(ListHolidays::class)
        ->assertOk();
});

it('allows manager to access the holidays list page', function () {
    actingAs(User::factory()->manager()->create());

    Livewire::test(ListHolidays::class)
        ->assertOk();
});

it('prevents employee from accessing the holidays list page', function () {
    actingAs(User::factory()->employee()->create());

    Livewire::test(ListHolidays::class)
        ->assertForbidden();
});

// --- Listing ---

it('displays holidays in the table', function () {
    actingAs(User::factory()->admin()->create());

    $holidays = Holiday::factory()->count(3)->create();

    Livewire::test(ListHolidays::class)
        ->assertCanSeeTableRecords($holidays);
});

it('can search holidays by name', function () {
    actingAs(User::factory()->admin()->create());

    $holiday = Holiday::factory()->create(['name' => 'Carnaval']);
    $other = Holiday::factory()->create(['name' => 'Ano Novo']);

    Livewire::test(ListHolidays::class)
        ->searchTable('Carnaval')
        ->assertCanSeeTableRecords(collect([$holiday]))
        ->assertCanNotSeeTableRecords(collect([$other]));
});

it('can filter holidays by type', function () {
    actingAs(User::factory()->admin()->create());

    $holiday = Holiday::factory()->create(['type' => HolidayType::Holiday]);
    $optional = Holiday::factory()->optional()->create();

    Livewire::test(ListHolidays::class)
        ->filterTable('type', HolidayType::Holiday->value)
        ->assertCanSeeTableRecords(collect([$holiday]))
        ->assertCanNotSeeTableRecords(collect([$optional]));
});

it('can filter holidays by month', function () {
    actingAs(User::factory()->admin()->create());

    $jan = Holiday::factory()->create(['date' => '2026-01-01']);
    $feb = Holiday::factory()->create(['date' => '2026-02-15']);

    Livewire::test(ListHolidays::class)
        ->filterTable('month', '1')
        ->assertCanSeeTableRecords(collect([$jan]))
        ->assertCanNotSeeTableRecords(collect([$feb]));
});

it('can filter holidays by year', function () {
    actingAs(User::factory()->admin()->create());

    $thisYear = Holiday::factory()->create(['date' => '2026-06-01']);
    $lastYear = Holiday::factory()->create(['date' => '2025-06-01']);

    Livewire::test(ListHolidays::class)
        ->filterTable('year', '2026')
        ->assertCanSeeTableRecords(collect([$thisYear]))
        ->assertCanNotSeeTableRecords(collect([$lastYear]));
});

// --- Create ---

it('allows admin to access the create holiday page', function () {
    actingAs(User::factory()->admin()->create());

    Livewire::test(CreateHoliday::class)
        ->assertOk();
});

it('allows manager to access the create holiday page', function () {
    actingAs(User::factory()->manager()->create());

    Livewire::test(CreateHoliday::class)
        ->assertOk();
});

it('can create a holiday with scope all', function () {
    actingAs(User::factory()->admin()->create());

    Livewire::test(CreateHoliday::class)
        ->fillForm([
            'name' => 'Dia do Trabalho',
            'date' => '2026-05-01',
            'type' => HolidayType::Holiday->value,
            'recurrent' => true,
            'scope' => HolidayScope::All->value,
        ])
        ->call('create')
        ->assertNotified()
        ->assertRedirect();

    $holiday = Holiday::where('name', 'Dia do Trabalho')->first();

    expect($holiday)->not->toBeNull()
        ->and($holiday->date->format('Y-m-d'))->toBe('2026-05-01')
        ->and($holiday->type)->toBe(HolidayType::Holiday)
        ->and($holiday->recurrent)->toBeTrue()
        ->and($holiday->scope)->toBe(HolidayScope::All);
});

it('can create a partial holiday with dispensed employees', function () {
    actingAs(User::factory()->admin()->create());

    $employee = Employee::factory()->create();

    Livewire::test(CreateHoliday::class)
        ->fillForm([
            'name' => 'Dispensa para evento',
            'date' => '2026-03-15',
            'type' => HolidayType::Partial->value,
            'recurrent' => false,
            'scope' => HolidayScope::Partial->value,
        ])
        ->fillForm([
            'employeeHolidays' => [
                [
                    'employee_id' => $employee->id,
                    'reason' => 'Convocação para evento externo',
                ],
            ],
        ])
        ->call('create')
        ->assertNotified()
        ->assertRedirect();

    $holiday = Holiday::where('name', 'Dispensa para evento')->first();

    expect($holiday)->not->toBeNull()
        ->and($holiday->scope)->toBe(HolidayScope::Partial)
        ->and($holiday->employees)->toHaveCount(1)
        ->and($holiday->employees->first()->id)->toBe($employee->id)
        ->and($holiday->employees->first()->pivot->reason)->toBe('Convocação para evento externo');
});

it('can create an optional holiday', function () {
    actingAs(User::factory()->manager()->create());

    Livewire::test(CreateHoliday::class)
        ->fillForm([
            'name' => 'Ponto Facultativo de Carnaval',
            'date' => '2026-02-17',
            'type' => HolidayType::Optional->value,
            'recurrent' => true,
            'scope' => HolidayScope::All->value,
        ])
        ->call('create')
        ->assertNotified()
        ->assertRedirect();

    assertDatabaseHas(Holiday::class, [
        'name' => 'Ponto Facultativo de Carnaval',
        'type' => HolidayType::Optional->value,
    ]);
});

it('validates required fields on create', function (array $data, array $errors) {
    actingAs(User::factory()->admin()->create());

    Livewire::test(CreateHoliday::class)
        ->fillForm([
            'name' => 'Test',
            'date' => '2026-01-01',
            'type' => HolidayType::Holiday->value,
            'scope' => HolidayScope::All->value,
            ...$data,
        ])
        ->call('create')
        ->assertHasFormErrors($errors)
        ->assertNotNotified()
        ->assertNoRedirect();
})->with([
    '`name` is required' => [['name' => null], ['name' => 'required']],
    '`date` is required' => [['date' => null], ['date' => 'required']],
    '`type` is required' => [['type' => null], ['type' => 'required']],
    '`scope` is required' => [['scope' => null], ['scope' => 'required']],
]);

// --- Edit ---

it('allows admin to access the edit holiday page', function () {
    actingAs(User::factory()->admin()->create());

    $holiday = Holiday::factory()->create();

    Livewire::test(EditHoliday::class, ['record' => $holiday->id])
        ->assertOk();
});

it('allows manager to access the edit holiday page', function () {
    actingAs(User::factory()->manager()->create());

    $holiday = Holiday::factory()->create();

    Livewire::test(EditHoliday::class, ['record' => $holiday->id])
        ->assertOk();
});

it('can update a holiday', function () {
    actingAs(User::factory()->admin()->create());

    $holiday = Holiday::factory()->create();

    Livewire::test(EditHoliday::class, ['record' => $holiday->id])
        ->fillForm([
            'name' => 'Feriado Atualizado',
            'date' => '2026-12-25',
            'type' => HolidayType::Holiday->value,
            'recurrent' => true,
            'scope' => HolidayScope::All->value,
        ])
        ->call('save')
        ->assertNotified();

    $holiday->refresh();

    expect($holiday->name)->toBe('Feriado Atualizado')
        ->and($holiday->date->format('Y-m-d'))->toBe('2026-12-25')
        ->and($holiday->recurrent)->toBeTrue();
});

it('can update a holiday to partial scope with employees', function () {
    actingAs(User::factory()->admin()->create());

    $holiday = Holiday::factory()->create([
        'scope' => HolidayScope::All,
    ]);
    $employee = Employee::factory()->create();

    Livewire::test(EditHoliday::class, ['record' => $holiday->id])
        ->fillForm([
            'name' => $holiday->name,
            'date' => $holiday->date->format('Y-m-d'),
            'type' => HolidayType::Partial->value,
            'recurrent' => false,
            'scope' => HolidayScope::Partial->value,
        ])
        ->fillForm([
            'employeeHolidays' => [
                [
                    'employee_id' => $employee->id,
                    'reason' => 'Motivo da dispensa',
                ],
            ],
        ])
        ->call('save')
        ->assertNotified();

    $holiday->refresh();

    expect($holiday->scope)->toBe(HolidayScope::Partial)
        ->and($holiday->employees)->toHaveCount(1)
        ->and($holiday->employees->first()->pivot->reason)->toBe('Motivo da dispensa');
});

// --- Delete ---

it('can delete a holiday', function () {
    actingAs(User::factory()->admin()->create());

    $holiday = Holiday::factory()->create();

    Livewire::test(EditHoliday::class, ['record' => $holiday->id])
        ->callAction('delete')
        ->assertNotified()
        ->assertRedirect();

    expect(Holiday::find($holiday->id))->toBeNull();
});
