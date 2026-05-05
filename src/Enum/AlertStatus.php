<?php

namespace App\Enum;

enum AlertStatus : string
{
    case SIGNALEMENT_PASSEE = 'signalement_passee';
    case SIGNALEMENT_VALIDEE = 'signalement_validee';
    case SIGNALEMENT_REFUSEE = 'signalement_refusee';
}
