<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum NavigationGroup implements HasLabel
{
    case Timesheet;
    case Attendance;
    case Administration;

    public function getLabel(): string
    {
        return match ($this) {
            self::Timesheet => 'Meu Ponto',
            self::Attendance => 'Frequência',
            self::Administration => 'Administração',
        };
    }
}
