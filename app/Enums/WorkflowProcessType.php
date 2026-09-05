<?php

namespace App\Enums;

/**
 * Blueprint §27's own list of 8 processes the engine should support.
 * Only `EmployeeInformationChange` has a real consumer wired up so far
 * (Phase 20c) -- the other 7 (Leave, Overtime, Salary Adjustment,
 * Promotion, COE, Document Request, Training Request) already have
 * their own bespoke approval flow, or none yet, and migrating them onto
 * this engine is a deliberate, separate follow-up (see CLAUDE.md
 * "Workflow"), not done here. The cases still exist so a company can
 * define a definition for any of them through the admin UI even before
 * a specific module reads it.
 */
enum WorkflowProcessType: string
{
    case Leave = 'leave';
    case Overtime = 'overtime';
    case SalaryAdjustment = 'salary_adjustment';
    case Promotion = 'promotion';
    case Coe = 'coe';
    case EmployeeInformationChange = 'employee_information_change';
    case DocumentRequest = 'document_request';
    case TrainingRequest = 'training_request';

    public function label(): string
    {
        return match ($this) {
            self::Leave => 'Leave',
            self::Overtime => 'Overtime',
            self::SalaryAdjustment => 'Salary Adjustment',
            self::Promotion => 'Promotion',
            self::Coe => 'Certificate of Employment',
            self::EmployeeInformationChange => 'Employee Information Change',
            self::DocumentRequest => 'Document Request',
            self::TrainingRequest => 'Training Request',
        };
    }
}
