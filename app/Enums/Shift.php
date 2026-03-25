<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Shift: string implements HasLabel
{
    case Morning = 'morning';
    case Afternoon = 'afternoon';

    public function getLabel(): string
    {
        return match ($this) {
            self::Morning => 'Manhã',
            self::Afternoon => 'Tarde',
        };
    }

    /**
     * @return array<string, string>
     */
    public function getFieldSequence(): array
    {
        return match ($this) {
            self::Morning => [
                'morning_entry' => 'Entrada Manhã',
                'morning_exit' => 'Saída Manhã',
            ],
            self::Afternoon => [
                'afternoon_entry' => 'Entrada Tarde',
                'afternoon_exit' => 'Saída Tarde',
            ],
        };
    }

    /**
     * @return array{morning_entry?: string, morning_exit?: string, afternoon_entry?: string, afternoon_exit?: string}
     */
    public function getFixedTimes(): array
    {
        return match ($this) {
            self::Morning => [
                'morning_entry' => '08:00',
                'morning_exit' => '12:00',
            ],
            self::Afternoon => [
                'afternoon_entry' => '13:00',
                'afternoon_exit' => '19:00',
            ],
        };
    }
}
