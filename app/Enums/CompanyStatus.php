<?php

namespace App\Enums;

enum CompanyStatus: string
{
    case Spotted = 'spotted';
    case Contacted = 'contacted';
    case OfferReceived = 'offer_received';
    case JobDeclined = 'job_declined';

    public function label(): string
    {
        return match ($this) {
            self::Spotted => 'Spotted',
            self::Contacted => 'Contacted',
            self::OfferReceived => 'Offer received',
            self::JobDeclined => 'Job declined',
        };
    }
}
