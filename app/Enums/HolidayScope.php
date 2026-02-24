<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum HolidayScope: string implements HasLabel
{
    case All = 'all';
    case Partial = 'partial';

    public function getLabel(): string
    {
        return match ($this) {
            self::All => 'Todos os colaboradores',
            self::Partial => 'Apenas selecionados',
        };
    }
}
