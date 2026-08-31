<?php

namespace App\Enums;

/**
 * Blueprint §15's full state machine. Phase 11 only wires transitions
 * through ForReview (Draft -> Processing -> ForReview); ForApproval
 * through Locked, and Cancelled, are reserved for Phase 12 (Payroll
 * Approval & Digital Payslip) -- same "row exists before the phase that
 * checks it" pattern as the seeded permission catalog.
 */
enum PayrollPeriodStatus: string
{
    case Draft = 'draft';
    case Processing = 'processing';
    case ForReview = 'for_review';
    case ForApproval = 'for_approval';
    case Approved = 'approved';
    case Finalized = 'finalized';
    case Published = 'published';
    case Locked = 'locked';
    case Cancelled = 'cancelled';
}
