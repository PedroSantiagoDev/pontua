<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TipoFeriado: string implements HasLabel
{
    case National = 'national';
    case State = 'state';
    case Municipal = 'municipal';

    public function getLabel(): string
    {
        return match ($this) {
            self::National => 'Nacional',
            self::State => 'Estadual',
            self::Municipal => 'Municipal',
        };
    }
}
