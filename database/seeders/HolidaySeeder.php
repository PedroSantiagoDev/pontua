<?php

namespace Database\Seeders;

use App\Enums\TipoFeriado;
use App\Models\Holiday;
use Illuminate\Database\Seeder;

class HolidaySeeder extends Seeder
{
    public function run(): void
    {
        $nationalHolidays = [
            ['date' => '2026-01-01', 'description' => 'Confraternização Universal'],
            ['date' => '2026-02-16', 'description' => 'Carnaval'],
            ['date' => '2026-02-17', 'description' => 'Carnaval'],
            ['date' => '2026-02-18', 'description' => 'Quarta-feira de Cinzas (ponto facultativo)'],
            ['date' => '2026-04-03', 'description' => 'Sexta-feira Santa'],
            ['date' => '2026-04-05', 'description' => 'Páscoa'],
            ['date' => '2026-04-21', 'description' => 'Tiradentes'],
            ['date' => '2026-05-01', 'description' => 'Dia do Trabalho'],
            ['date' => '2026-06-04', 'description' => 'Corpus Christi'],
            ['date' => '2026-09-07', 'description' => 'Independência do Brasil'],
            ['date' => '2026-10-12', 'description' => 'Nossa Senhora Aparecida'],
            ['date' => '2026-11-02', 'description' => 'Finados'],
            ['date' => '2026-11-15', 'description' => 'Proclamação da República'],
            ['date' => '2026-11-20', 'description' => 'Consciência Negra'],
            ['date' => '2026-12-25', 'description' => 'Natal'],
        ];

        foreach ($nationalHolidays as $holiday) {
            Holiday::create([
                'date' => $holiday['date'],
                'description' => $holiday['description'],
                'type' => TipoFeriado::National,
            ]);
        }

        // Maranhão state holiday
        Holiday::create([
            'date' => '2026-07-28',
            'description' => 'Adesão do Maranhão à Independência',
            'type' => TipoFeriado::State,
        ]);
    }
}
