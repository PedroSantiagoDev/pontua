<?php

use App\Enums\Shift;
use App\Filament\Widgets\ClockInWidget;
use App\Models\Employee;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

// --- Visibility ---

it('is visible to employee users', function () {
    $employee = Employee::factory()->create();

    actingAs($employee->user);

    Livewire::test(ClockInWidget::class)
        ->assertOk();
});

it('is not visible to admin users', function () {
    $admin = User::factory()->admin()->create();

    expect(ClockInWidget::canView())->toBeFalse();
});

it('is not visible to manager users', function () {
    $manager = User::factory()->manager()->create();

    actingAs($manager);

    expect(ClockInWidget::canView())->toBeFalse();
});

// --- Morning shift clock in ---

it('clocks in morning entry for morning shift employee', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-24 08:05'));

    $employee = Employee::factory()->morning()->create();

    actingAs($employee->user);

    Livewire::test(ClockInWidget::class)
        ->call('clockIn')
        ->assertNotified();

    $entry = TimeEntry::where('employee_id', $employee->id)
        ->whereDate('date', '2026-02-24')
        ->first();

    expect($entry)->not->toBeNull()
        ->and($entry->morning_entry)->toBe('08:05')
        ->and($entry->morning_exit)->toBeNull()
        ->and($entry->afternoon_entry)->toBeNull()
        ->and($entry->afternoon_exit)->toBeNull()
        ->and($entry->shift_override)->toBeNull();
});

it('clocks in morning exit after morning entry', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-24 14:00'));

    $employee = Employee::factory()->morning()->create();

    TimeEntry::factory()->for($employee)->create([
        'date' => '2026-02-24',
        'morning_entry' => '08:05',
        'morning_exit' => null,
        'afternoon_entry' => null,
        'afternoon_exit' => null,
    ]);

    actingAs($employee->user);

    Livewire::test(ClockInWidget::class)
        ->call('clockIn')
        ->assertNotified();

    $entry = TimeEntry::where('employee_id', $employee->id)
        ->whereDate('date', '2026-02-24')
        ->first();

    expect($entry->morning_exit)->toBe('14:00');
});

it('shows all done when morning shift is complete', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-24 14:30'));

    $employee = Employee::factory()->morning()->create();

    TimeEntry::factory()->for($employee)->morning()->create([
        'date' => '2026-02-24',
    ]);

    actingAs($employee->user);

    $widget = Livewire::test(ClockInWidget::class);

    expect($widget->instance()->getNextField())->toBeNull();

    $widget->call('clockIn')
        ->assertNotified();
});

// --- Afternoon shift clock in ---

it('clocks in afternoon entry for afternoon shift employee', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-24 13:10'));

    $employee = Employee::factory()->afternoon()->create();

    actingAs($employee->user);

    Livewire::test(ClockInWidget::class)
        ->call('clockIn')
        ->assertNotified();

    $entry = TimeEntry::where('employee_id', $employee->id)
        ->whereDate('date', '2026-02-24')
        ->first();

    expect($entry)->not->toBeNull()
        ->and($entry->afternoon_entry)->toBe('13:10')
        ->and($entry->afternoon_exit)->toBeNull()
        ->and($entry->morning_entry)->toBeNull()
        ->and($entry->morning_exit)->toBeNull();
});

it('clocks in afternoon exit after afternoon entry', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-24 19:00'));

    $employee = Employee::factory()->afternoon()->create();

    TimeEntry::factory()->for($employee)->create([
        'date' => '2026-02-24',
        'morning_entry' => null,
        'morning_exit' => null,
        'afternoon_entry' => '13:10',
        'afternoon_exit' => null,
    ]);

    actingAs($employee->user);

    Livewire::test(ClockInWidget::class)
        ->call('clockIn')
        ->assertNotified();

    $entry = TimeEntry::where('employee_id', $employee->id)
        ->whereDate('date', '2026-02-24')
        ->first();

    expect($entry->afternoon_exit)->toBe('19:00');
});

// --- Shift override ---

it('allows shift override and records it on time entry', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-24 08:00'));

    $employee = Employee::factory()->afternoon()->create();

    actingAs($employee->user);

    Livewire::test(ClockInWidget::class)
        ->set('selectedShift', 'morning')
        ->call('clockIn')
        ->assertNotified();

    $entry = TimeEntry::where('employee_id', $employee->id)
        ->whereDate('date', '2026-02-24')
        ->first();

    expect($entry)->not->toBeNull()
        ->and($entry->shift_override)->toBe(Shift::Morning)
        ->and($entry->morning_entry)->toBe('08:00');
});

it('does not set shift override when using default shift', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-24 08:00'));

    $employee = Employee::factory()->morning()->create();

    actingAs($employee->user);

    Livewire::test(ClockInWidget::class)
        ->call('clockIn')
        ->assertNotified();

    $entry = TimeEntry::where('employee_id', $employee->id)
        ->whereDate('date', '2026-02-24')
        ->first();

    expect($entry->shift_override)->toBeNull();
});

// --- Sequential clock in (full flow) ---

it('handles full morning clock in flow sequentially', function () {
    $employee = Employee::factory()->morning()->create();

    actingAs($employee->user);

    Carbon::setTestNow(Carbon::parse('2026-02-24 08:00'));

    Livewire::test(ClockInWidget::class)
        ->call('clockIn')
        ->assertNotified();

    Carbon::setTestNow(Carbon::parse('2026-02-24 14:00'));

    Livewire::test(ClockInWidget::class)
        ->call('clockIn')
        ->assertNotified();

    $entry = TimeEntry::where('employee_id', $employee->id)
        ->whereDate('date', '2026-02-24')
        ->first();

    expect($entry->morning_entry)->toBe('08:00')
        ->and($entry->morning_exit)->toBe('14:00');
});

// --- Defaults ---

it('defaults selected shift to employee default shift', function () {
    $employee = Employee::factory()->afternoon()->create();

    actingAs($employee->user);

    $widget = Livewire::test(ClockInWidget::class);

    expect($widget->instance()->selectedShift)->toBe('afternoon');
});

it('defaults selected shift to shift override from existing entry', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-24 08:00'));

    $employee = Employee::factory()->afternoon()->create();

    TimeEntry::factory()->for($employee)->create([
        'date' => '2026-02-24',
        'morning_entry' => '08:00',
        'morning_exit' => null,
        'afternoon_entry' => null,
        'afternoon_exit' => null,
        'shift_override' => Shift::Morning,
    ]);

    actingAs($employee->user);

    $widget = Livewire::test(ClockInWidget::class);

    expect($widget->instance()->selectedShift)->toBe('morning');
});
