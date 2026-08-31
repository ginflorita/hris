<?php

namespace App\Enums;

/**
 * Blueprint §8's applicant lifecycle diagram (Application -> Screening ->
 * Interview -> Assessment -> Final Interview -> Job Offer -> Hired),
 * plus the two terminal exits (Rejected, Withdrawn) reachable from any
 * non-terminal stage. ApplicationController::updateStatus() doesn't
 * enforce strict linear ordering -- see CLAUDE.md "Recruitment" for why.
 */
enum ApplicationStatus: string
{
    case Applied = 'applied';
    case Screening = 'screening';
    case Interview = 'interview';
    case Assessment = 'assessment';
    case FinalInterview = 'final_interview';
    case Offered = 'offered';
    case Hired = 'hired';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';

    public function isTerminal(): bool
    {
        return in_array($this, [self::Hired, self::Rejected, self::Withdrawn], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::Applied => 'Applied',
            self::Screening => 'Screening',
            self::Interview => 'Interview',
            self::Assessment => 'Assessment',
            self::FinalInterview => 'Final Interview',
            self::Offered => 'Offered',
            self::Hired => 'Hired',
            self::Rejected => 'Rejected',
            self::Withdrawn => 'Withdrawn',
        };
    }
}
