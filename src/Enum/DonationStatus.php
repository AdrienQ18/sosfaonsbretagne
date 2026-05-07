<?php

namespace App\Enum;

enum DonationStatus : string
{
    case DONATION_PASSEE = 'donation_passee';
    case DONATION_VALIDEE = 'donation_validee';
    case DONATION_REFUSEE = 'donation_refusee';
}
