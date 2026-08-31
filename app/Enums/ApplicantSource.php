<?php

namespace App\Enums;

enum ApplicantSource: string
{
    case Referral = 'referral';
    case JobBoard = 'job_board';
    case CompanyWebsite = 'company_website';
    case SocialMedia = 'social_media';
    case Agency = 'agency';
    case WalkIn = 'walk_in';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Referral => 'Referral',
            self::JobBoard => 'Job Board',
            self::CompanyWebsite => 'Company Website',
            self::SocialMedia => 'Social Media',
            self::Agency => 'Agency',
            self::WalkIn => 'Walk-in',
            self::Other => 'Other',
        };
    }
}
