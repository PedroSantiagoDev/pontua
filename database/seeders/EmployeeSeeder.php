<?php

namespace Database\Seeders;

use App\Enums\Perfil;
use App\Enums\Turno;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user — no employee link
        User::create([
            'name' => 'Administrador AGED',
            'email' => 'admin@aged.ma.gov.br',
            'password' => Hash::make('password'),
            'role' => Perfil::Administrator,
            'email_verified_at' => now(),
        ]);

        // Manager employee + user
        $managerEmployee = Employee::create([
            'registration_number' => '100001',
            'name' => 'Responsável AGED',
            'department' => 'Administração Geral',
            'position' => 'Chefe de Setor',
            'shift' => Turno::Morning,
            'payroll_code' => 'RUB-0001',
            'active' => true,
        ]);

        User::create([
            'employee_id' => $managerEmployee->id,
            'name' => $managerEmployee->name,
            'email' => 'responsavel@aged.ma.gov.br',
            'password' => Hash::make('password'),
            'role' => Perfil::Manager,
            'email_verified_at' => now(),
        ]);

        // 10 staff employees with linked users
        Employee::factory(10)->create()->each(function (Employee $employee) {
            User::factory()->staff()->create([
                'employee_id' => $employee->id,
                'name' => $employee->name,
            ]);
        });
    }
}
