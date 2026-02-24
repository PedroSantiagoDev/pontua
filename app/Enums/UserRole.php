<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasColor, HasLabel
{
    case Admin = 'admin';
    case Manager = 'manager';
    case Employee = 'employee';

    public function getLabel(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Manager => 'Gestor',
            self::Employee => 'Colaborador',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Admin => 'danger',
            self::Manager => 'warning',
            self::Employee => 'success',
        };
    }
}
