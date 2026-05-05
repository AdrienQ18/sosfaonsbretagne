<?php

namespace App\Enum;

enum PreOrderStatus : string
{
    case PRECOMMANDE_PASSEE = 'precommande_passee';
    case PRECOMMANDE_VALIDEE = 'precommande_validee';
    case PRECOMMANDE_REFUSEE = 'precommande_refusee';
}
