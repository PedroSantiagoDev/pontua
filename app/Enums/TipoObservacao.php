<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TipoObservacao: string implements HasLabel
{
    case Absence = 'absence';
    case JustifiedAbsence = 'justified_absence';
    case Released = 'released';
    case Vacation = 'vacation';
    case MedicalLeave = 'medical_leave';
    case ExternalService = 'external_service';
    case Free = 'free';

    public function getLabel(): string
    {
        return match ($this) {
            self::Absence => 'Falta',
            self::JustifiedAbsence => 'Falta Justificada',
            self::Released => 'Liberado',
            self::Vacation => 'Férias',
            self::MedicalLeave => 'Afastamento Médico',
            self::ExternalService => 'Serviço Externo',
            self::Free => 'Livre',
        };
    }
}
