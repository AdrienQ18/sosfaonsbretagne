<?php

namespace App\Enum;

enum AlertStatus : string
{
    case SIGNALEMENT_PASSEE = 'signalement_passee';
    case SIGNALEMENT_VALIDEE = 'signalement_validee';
    case SIGNALEMENT_REFUSEE = 'signalement_refusee';
    public function getLabel(): string
    {
        return match ($this) {
            self::SIGNALEMENT_PASSEE => 'Signalement passé',
            self::SIGNALEMENT_VALIDEE => 'Intervention validée',
            self::SIGNALEMENT_REFUSEE => 'Intervention refusée',
        };
    }
}
