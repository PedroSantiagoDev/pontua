<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        if (User::where('role', UserRole::Admin)->exists()) {
            return;
        }

        User::create([
            'name' => 'Administrador',
            'email' => 'admin@pontua.com',
            'password' => 'password',
            'role' => UserRole::Admin,
        ]);
    }
}
