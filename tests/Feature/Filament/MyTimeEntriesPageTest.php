<?php

use App\Enums\HolidayScope;
use App\Enums\HolidayType;
use App\Filament\Pages\MyTimeEntries;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

// --- Access control ---

it('is accessible to employee users', function () {
    $employee = Employee::factory()->create();

    actingAs($employee->user);

    Livewire::test(MyTimeEntries::class)
        ->assertOk();
});

it('is not accessible to admin users', function () {
    $admin = User::factory()->admin()->create();

    actingAs($admin);

    Livewire::test(MyTimeEntries::class)
        ->assertForbidden();
});

it('is not accessible to manager users', function () {
    $manager = User::factory()->manager()->create();

    actingAs($manager);

    Livewire::test(MyTimeEntries::class)
        ->assertForbidden();
});

// --- Defaults ---

it('defaults to current month and year', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-15'));

    $employee = Employee::factory()->create();

    actingAs($employee->user);

    $page = Livewire::test(MyTimeEntries::class);

    expect($page->instance()->selectedMonth)->toBe(3)
        ->and($page->instance()->selectedYear)->toBe(2026);
});

it('returns correct number of days for the selected month', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-15'));

    $employee = Employee::factory()->create();

    actingAs($employee->user);

    $page = Livewire::test(MyTimeEntries::class);
    $days = $page->instance()->getCalendarDays();

    expect($days)->toHaveCount(28);
});

// --- Time entries ---

it('displays time entries for days with records', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-24'));

    $employee = Employee::factory()->create();

    TimeEntry::factory()->for($employee)->create([
        'date' => '2026-02-02',
        'morning_entry' => '08:00',
        'morning_exit' => '14:00',
        'afternoon_entry' => null,
        'afternoon_exit' => null,
    ]);

    actingAs($employee->user);

    $page = Livewire::test(MyTimeEntries::class);
    $days = $page->instance()->getCalendarDays();

    $feb2 = $days[1]; // index 1 = day 2

    expect($feb2['type'])->toBe('present')
        ->and($feb2['morning_entry'])->toBe('08:00')
        ->and($feb2['morning_exit'])->toBe('14:00');
});

it('marks past weekdays without entries as absent', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-24'));

    $employee = Employee::factory()->create();

    actingAs($employee->user);

    $page = Livewire::test(MyTimeEntries::class);
    $days = $page->instance()->getCalendarDays();

    // Feb 2, 2026 is Monday - no entries should be FALTA
    $feb2 = $days[1];

    expect($feb2['type'])->toBe('absent')
        ->and($feb2['observation'])->toBe('FALTA');
});

// --- Weekends ---

it('identifies weekends correctly', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-24'));

    $employee = Employee::factory()->create();

    actingAs($employee->user);

    $page = Livewire::test(MyTimeEntries::class);
    $days = $page->instance()->getCalendarDays();

    // Feb 1, 2026 is Sunday
    $feb1 = $days[0];

    expect($feb1['type'])->toBe('weekend')
        ->and($feb1['observation'])->toBe('Fim de semana');

    // Feb 7, 2026 is Saturday
    $feb7 = $days[6];

    expect($feb7['type'])->toBe('weekend');
});

// --- Holidays ---

it('identifies holidays with scope all', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-24'));

    $employee = Employee::factory()->create();

    Holiday::factory()->create([
        'date' => '2026-02-09',
        'name' => 'Carnaval',
        'type' => HolidayType::Holiday,
        'scope' => HolidayScope::All,
        'recurrent' => false,
    ]);

    actingAs($employee->user);

    $page = Livewire::test(MyTimeEntries::class);
    $days = $page->instance()->getCalendarDays();

    $feb9 = $days[8];

    expect($feb9['type'])->toBe('holiday')
        ->and($feb9['observation'])->toBe('Carnaval');
});

it('identifies optional holidays', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-24'));

    $employee = Employee::factory()->create();

    Holiday::factory()->optional()->create([
        'date' => '2026-02-10',
        'name' => 'Ponto Facultativo Carnaval',
        'scope' => HolidayScope::All,
        'recurrent' => false,
    ]);

    actingAs($employee->user);

    $page = Livewire::test(MyTimeEntries::class);
    $days = $page->instance()->getCalendarDays();

    $feb10 = $days[9];

    expect($feb10['type'])->toBe('optional')
        ->and($feb10['observation'])->toBe('Ponto Facultativo Carnaval');
});

it('identifies recurrent holidays by month and day', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-24'));

    $employee = Employee::factory()->create();

    Holiday::factory()->create([
        'date' => '2020-02-05',
        'name' => 'Feriado Recorrente',
        'type' => HolidayType::Holiday,
        'scope' => HolidayScope::All,
        'recurrent' => true,
    ]);

    actingAs($employee->user);

    $page = Livewire::test(MyTimeEntries::class);
    $days = $page->instance()->getCalendarDays();

    $feb5 = $days[4];

    expect($feb5['type'])->toBe('holiday')
        ->and($feb5['observation'])->toBe('Feriado Recorrente');
});

// --- Dispensations ---

it('shows dispensation for employee included in partial scope', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-24'));

    $employee = Employee::factory()->create();

    $holiday = Holiday::factory()->partial()->create([
        'date' => '2026-02-03',
        'name' => 'Dispensa Administrativa',
        'recurrent' => false,
    ]);

    $holiday->employees()->attach($employee->id, ['reason' => 'Motivo especial']);

    actingAs($employee->user);

    $page = Livewire::test(MyTimeEntries::class);
    $days = $page->instance()->getCalendarDays();

    $feb3 = $days[2];

    expect($feb3['type'])->toBe('dispensation')
        ->and($feb3['observation'])->toContain('Dispensa Administrativa')
        ->and($feb3['observation'])->toContain('Motivo especial');
});

it('does not show dispensation for employee not included in partial scope', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-24'));

    $employee = Employee::factory()->create();
    $otherEmployee = Employee::factory()->create();

    $holiday = Holiday::factory()->partial()->create([
        'date' => '2026-02-03',
        'name' => 'Dispensa Administrativa',
        'recurrent' => false,
    ]);

    $holiday->employees()->attach($otherEmployee->id, ['reason' => 'Motivo']);

    actingAs($employee->user);

    $page = Livewire::test(MyTimeEntries::class);
    $days = $page->instance()->getCalendarDays();

    $feb3 = $days[2];

    expect($feb3['type'])->toBe('absent');
});

it('shows dispensation with scope all for all employees', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-24'));

    $employee = Employee::factory()->create();

    Holiday::factory()->create([
        'date' => '2026-02-03',
        'name' => 'Dispensa Geral',
        'type' => HolidayType::Partial,
        'scope' => HolidayScope::All,
        'recurrent' => false,
    ]);

    actingAs($employee->user);

    $page = Livewire::test(MyTimeEntries::class);
    $days = $page->instance()->getCalendarDays();

    $feb3 = $days[2];

    expect($feb3['type'])->toBe('dispensation')
        ->and($feb3['observation'])->toContain('Dispensa Geral');
});

// --- Future days ---

it('does not mark future days as absent', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-15'));

    $employee = Employee::factory()->create();

    actingAs($employee->user);

    $page = Livewire::test(MyTimeEntries::class);
    $days = $page->instance()->getCalendarDays();

    // Feb 16, 2026 is Monday (future weekday)
    $feb16 = $days[15];

    expect($feb16['type'])->toBe('future')
        ->and($feb16['observation'])->toBeNull();
});

// --- Filter ---

it('updates calendar when changing month and year', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-15'));

    $employee = Employee::factory()->create();

    TimeEntry::factory()->for($employee)->create([
        'date' => '2026-01-05',
        'morning_entry' => '08:00',
        'morning_exit' => '14:00',
    ]);

    actingAs($employee->user);

    $page = Livewire::test(MyTimeEntries::class)
        ->set('selectedMonth', 1)
        ->set('selectedYear', 2026);

    $days = $page->instance()->getCalendarDays();

    expect($days)->toHaveCount(31);

    $jan5 = $days[4];

    expect($jan5['type'])->toBe('present')
        ->and($jan5['morning_entry'])->toBe('08:00');
});
