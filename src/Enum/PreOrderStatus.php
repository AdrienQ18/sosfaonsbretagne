<?php

namespace App\Enum;

enum PreOrderStatus : string
{
    case EN_ATTENTE = 'en_attente';
    case VALIDEE = 'validee';
    case REFUSEE = 'refusee';
    case EN_ATTENTE_PAIEMENT = 'en_attente_paiement';
    case PAIEMENT_REFUSE = 'paiement_refuse';
    case PAYEE = 'payee';
}
