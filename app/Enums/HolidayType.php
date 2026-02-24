<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum HolidayType: string implements HasColor, HasLabel
{
    case Holiday = 'holiday';
    case Optional = 'optional';
    case Partial = 'partial';

    public function getLabel(): string
    {
        return match ($this) {
            self::Holiday => 'Feriado',
            self::Optional => 'Ponto Facultativo',
            self::Partial => 'Dispensa Parcial',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Holiday => 'danger',
            self::Optional => 'warning',
            self::Partial => 'info',
        };
    }
}
