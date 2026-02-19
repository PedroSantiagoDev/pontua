<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Perfil: string implements HasLabel
{
    case Staff = 'staff';
    case Manager = 'manager';
    case Administrator = 'administrator';

    public function getLabel(): string
    {
        return match ($this) {
            self::Staff => 'Servidor',
            self::Manager => 'Responsável',
            self::Administrator => 'Administrador',
        };
    }
}
