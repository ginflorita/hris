<?php

namespace App\Enums;

/**
 * The roles seeded by RoleAndPermissionSeeder (blueprint §32). Reference
 * these instead of a raw string wherever code needs to know about one of
 * them by name — the admin UI can still create additional custom roles,
 * this enum only names the well-known ones code has to treat specially
 * (Superadmin protection, mandatory MFA, ...).
 */
enum DefaultRole: string
{
    case Superadmin = 'Superadmin';
    case HrAdministrator = 'HR Administrator';
    case HrStaff = 'HR Staff';
    case PayrollAdministrator = 'Payroll Administrator';
    case AttendanceOfficer = 'Attendance Officer';
    case RecruitmentOfficer = 'Recruitment Officer';
    case TrainingOfficer = 'Training Officer';
    case PerformanceOfficer = 'Performance Officer';
    case Manager = 'Manager';
    case Employee = 'Employee';
}
