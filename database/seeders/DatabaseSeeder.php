<?php

namespace Database\Seeders;

use App\Enums\HolidayScope;
use App\Enums\HolidayType;
use App\Enums\Shift;
use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\TimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $admin = User::factory()->create([
            'name' => 'Administrador',
            'email' => 'admin@pontua.test',
            'role' => UserRole::Admin,
        ]);

        $manager = User::factory()->create([
            'name' => 'Gestor',
            'email' => 'gestor@pontua.test',
            'role' => UserRole::Manager,
        ]);

        $employeeUser1 = User::factory()->create([
            'name' => 'Maria Silva',
            'email' => 'maria@pontua.test',
            'role' => UserRole::Employee,
        ]);

        $employeeUser2 = User::factory()->create([
            'name' => 'João Santos',
            'email' => 'joao@pontua.test',
            'role' => UserRole::Employee,
        ]);

        $employee1 = Employee::factory()->create([
            'name' => 'Maria Silva',
            'inscription' => '100001',
            'department' => 'TI',
            'position' => 'Analista de Sistemas',
            'organization' => 'AGED-MA',
            'default_shift' => Shift::Morning,
            'user_id' => $employeeUser1->id,
        ]);

        $employee2 = Employee::factory()->create([
            'name' => 'João Santos',
            'inscription' => '100002',
            'department' => 'Administrativo',
            'position' => 'Assistente Administrativo',
            'organization' => 'AGED-MA',
            'default_shift' => Shift::Afternoon,
            'user_id' => $employeeUser2->id,
        ]);

        $today = Carbon::today();
        $startOfMonth = $today->copy()->startOfMonth();

        foreach ([$employee1, $employee2] as $employee) {
            $date = $startOfMonth->copy();
            while ($date->lte($today)) {
                if ($date->isWeekday()) {
                    $isMorning = $employee->default_shift === Shift::Morning;

                    TimeEntry::factory()->create([
                        'employee_id' => $employee->id,
                        'date' => $date->toDateString(),
                        'morning_entry' => $isMorning ? '08:00' : null,
                        'morning_exit' => $isMorning ? '14:00' : null,
                        'afternoon_entry' => $isMorning ? null : '13:00',
                        'afternoon_exit' => $isMorning ? null : '19:00',
                    ]);
                }
                $date->addDay();
            }
        }

        Holiday::factory()->create([
            'date' => Carbon::create($today->year, 1, 1),
            'name' => 'Confraternização Universal',
            'type' => HolidayType::Holiday,
            'recurrent' => true,
            'scope' => HolidayScope::All,
        ]);

        Holiday::factory()->create([
            'date' => Carbon::create($today->year, 4, 21),
            'name' => 'Tiradentes',
            'type' => HolidayType::Holiday,
            'recurrent' => true,
            'scope' => HolidayScope::All,
        ]);

        Holiday::factory()->create([
            'date' => Carbon::create($today->year, 5, 1),
            'name' => 'Dia do Trabalho',
            'type' => HolidayType::Holiday,
            'recurrent' => true,
            'scope' => HolidayScope::All,
        ]);

        $partialHoliday = Holiday::factory()->create([
            'date' => $today->copy()->addDays(5),
            'name' => 'Evento Institucional',
            'type' => HolidayType::Partial,
            'recurrent' => false,
            'scope' => HolidayScope::Partial,
        ]);

        $partialHoliday->employees()->attach($employee1->id, [
            'reason' => 'Convocação para evento externo',
        ]);
    }
}
