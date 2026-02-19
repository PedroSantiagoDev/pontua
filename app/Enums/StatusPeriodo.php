<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum StatusPeriodo: string implements HasLabel
{
    case Open = 'open';
    case Closed = 'closed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Open => 'Aberto',
            self::Closed => 'Fechado',
        };
    }
}
