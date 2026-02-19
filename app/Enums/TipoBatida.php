<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TipoBatida: string implements HasLabel
{
    case MorningEntry = 'morning_entry';
    case MorningExit = 'morning_exit';
    case AfternoonEntry = 'afternoon_entry';
    case AfternoonExit = 'afternoon_exit';

    public function getLabel(): string
    {
        return match ($this) {
            self::MorningEntry => 'Entrada Manhã',
            self::MorningExit => 'Saída Manhã',
            self::AfternoonEntry => 'Entrada Tarde',
            self::AfternoonExit => 'Saída Tarde',
        };
    }
}
