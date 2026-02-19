<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Turno: string implements HasLabel
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
}
