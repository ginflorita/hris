# HRIS Web Application System — Complete Blueprint

## 1. Project Overview

A modular Human Resources Information System (HRIS) built with:

- Laravel 12
- PHP 8.3+
- MySQL 8+
- Blade
- Bootstrap 5.3+
- Vite
- Alpine.js where useful
- Redis for queues/cache where available
- Nginx + PHP-FPM for production
- HTTPS/TLS
- Private file storage

### UI Requirements

- Minimalist
- Clean white theme
- Dark mode
- Responsive
- Sidebar navigation rather than a top navbar
- Mobile offcanvas sidebar
- Breadcrumbs
- Compact, readable data tables
- Reusable Bootstrap components
- Light / Dark / System color mode

---

# 2. Overall Architecture

```text
                    HRIS
                     │
       ┌─────────────┼─────────────┐
       │             │             │
   ADMIN PORTAL  MANAGER PORTAL  EMPLOYEE PORTAL
       │             │             │
       └─────────────┼─────────────┘
                     │
              AUTHENTICATION
                     │
              AUTHORIZATION
                     │
            RBAC + DATA SCOPE
                     │
              BUSINESS LOGIC
                     │
        ┌────────────┼────────────┐
        │            │            │
      HR CORE      PAYROLL     WORKFORCE
        │            │            │
        └────────────┼────────────┘
                     │
                  MySQL
                     │
              Audit / Logging
```

Security must be implemented throughout development, not only at the final security phase.

---

# 3. HRIS Modules

## Core HR

1. Dashboard
2. Employee Management
3. Organization Management
4. Employment Management
5. Position Management
6. Compensation
7. Employee Documents
8. Dependents
9. Employee History

## Workforce Management

10. Attendance
11. Work Schedules
12. Shifts
13. Overtime
14. Holidays
15. Leave Management

## Payroll

16. Payroll Configuration
17. Payroll Processing
18. Payroll Approval
19. Payroll Finalization
20. Digital Payslip
21. Government Contributions
22. Tax
23. 13th Month
24. Final Pay

## Employee Experience

25. Employee Self-Service
26. Manager Self-Service
27. Employee Requests
28. Notifications
29. Announcements
30. Certificate of Employment

## Talent

31. Recruitment
32. Applicant Tracking
33. Onboarding
34. Performance Management
35. Goals / KPIs
36. Learning & Development
37. Skills & Competencies
38. Career Development
39. Succession Planning

## Benefits

40. Benefits
41. Benefit Plans
42. Enrollment
43. Dependents
44. Benefit Contributions

## Administration

45. Users
46. Roles
47. Permissions
48. Workflows
49. System Settings
50. Audit Logs
51. Security
52. File Management

## Analytics and Reports

53. HR Reports
54. Payroll Reports
55. Attendance Reports
56. Leave Reports
57. Recruitment Reports
58. Performance Reports
59. Training Reports
60. Workforce Analytics

---

# 4. Employee Management

The employee record is the central HR entity.

## Employee Profile

- Employee number
- First name
- Middle name
- Last name
- Suffix
- Preferred name
- Birth date
- Gender
- Civil status
- Nationality
- Email
- Mobile
- Address
- Emergency contact
- Government IDs
- Bank information
- Profile photo

## CRUD

- Create
- Read
- Update
- Archive
- Restore
- Search
- Filter
- Export
- View history

### Important Rule

Do not permanently delete employees who have:

- Payroll
- Attendance
- Leave
- Benefits
- Employment records
- Government records

Use archive/separation instead.

---

# 5. Employment Management

Track:

- Employment type
- Hire date
- Probation date
- Regularization
- Contract
- Position
- Department
- Manager
- Branch
- Work location
- Work arrangement
- Employment status

Structure:

```text
Employee
   │
   ├── Employment
   ├── Position
   ├── Department
   ├── Manager
   ├── Location
   ├── Salary
   └── Schedule
```

---

# 6. Employment History

Never overwrite important historical information.

Example:

```text
2024
Junior Accountant
₱25,000
Accounting

2025
Accountant
₱30,000
Accounting

2026
Senior Accountant
₱40,000
Finance
```

Use effective-dated historical records.

---

# 7. Organization Management

Support:

```text
Company
 └── Division
      └── Department
           └── Section
                └── Team
                     └── Employee
```

Recommended tables:

```text
companies
branches
locations
divisions
departments
sections
teams
positions
job_levels
job_grades
cost_centers
```

---

# 8. Recruitment

Applicant lifecycle:

```text
Application
     ↓
Screening
     ↓
Interview
     ↓
Assessment
     ↓
Final Interview
     ↓
Job Offer
     ↓
Hired
     ↓
Onboarding
```

Functions:

- Job requisitions
- Job postings
- Applicant profiles
- Resume
- Applications
- Screening
- Interviews
- Assessments
- Interview scoring
- Job offers
- Applicant status
- Candidate pool
- Hiring conversion

---

# 9. Onboarding

Create onboarding templates.

Example:

```text
New Employee
 ├── Submit requirements
 ├── Sign contract
 ├── Orientation
 ├── Company ID
 ├── Equipment
 ├── System account
 ├── Department orientation
 └── Training
```

Track task completion percentage.

---

# 10. Attendance

Support multiple sources:

- Web
- Mobile
- Biometric
- RFID
- CSV import
- API
- Manual entry

Functions:

- Clock in
- Clock out
- Break
- Late
- Undertime
- Absence
- Overtime
- Night differential
- Holiday work
- Rest day work
- Attendance correction

---

# 11. Scheduling

Support:

- Fixed schedule
- Flexible schedule
- Shifts
- Rotating shifts
- Rest days
- Breaks
- Grace periods
- Night shift

Example:

```text
08:00 - 17:00
Break: 12:00 - 13:00
Grace: 10 minutes
```

---

# 12. Leave Management

Functions:

- Leave types
- Leave policies
- Leave credits
- Accrual
- Leave requests
- Approval
- Rejection
- Cancellation
- Adjustments
- Leave calendar
- Leave reports

Workflow:

```text
Employee
 ↓
Leave Request
 ↓
Manager
 ↓
HR
 ↓
Approved
 ↓
Balance Updated
```

---

# 13. Overtime

Functions:

- Overtime request
- Overtime approval
- Overtime calculation
- Overtime records
- Holiday overtime
- Rest day overtime
- Night differential

Use configurable rules instead of hard-coded calculations.

---

# 14. Payroll

Payroll should be treated as a separate business domain.

## Payroll Configuration

- Payroll groups
- Payroll frequency
- Payroll periods
- Earnings
- Deductions
- Contributions
- Tax
- Allowances
- Bonuses
- Overtime
- Holiday pay

## Payroll Lifecycle

```text
Create Period
     ↓
Collect Attendance
     ↓
Collect Leave
     ↓
Collect Overtime
     ↓
Load Compensation
     ↓
Calculate Earnings
     ↓
Calculate Deductions
     ↓
Calculate Contributions
     ↓
Calculate Tax
     ↓
Review
     ↓
Approve
     ↓
Finalize
     ↓
Lock
     ↓
Generate Payslips
     ↓
Publish
```

---

# 15. Payroll States

Use:

```text
DRAFT
PROCESSING
FOR_REVIEW
FOR_APPROVAL
APPROVED
FINALIZED
PUBLISHED
LOCKED
CANCELLED
```

Once finalized, normal editing must not be allowed.

Corrections should use:

```text
Payroll Adjustment
Payroll Reversal
Payroll Correction
```

---

# 16. Digital Payslip

Employee portal:

```text
Employee Portal
    ↓
My Payslips
    ↓
Payroll Period
    ↓
View
    ↓
Download PDF
```

## Payslip Earnings

- Basic salary
- Overtime
- Holiday pay
- Allowances
- Bonus
- Incentives

## Payslip Deductions

- SSS
- PhilHealth
- Pag-IBIG
- Withholding tax
- Loans
- Other deductions

## Summary

```text
Gross Pay
Total Deductions
Net Pay
```

---

# 17. Payslip Security

Never assume that an authenticated user may access any payslip.

Example:

```text
/payslips/123
```

must verify authorization.

Employee access:

```text
payslip.employee_id
==
auth()->user()->employee_id
```

unless the user has an appropriate payroll permission.

Log:

- Payslip viewed
- Payslip downloaded
- Payslip printed
- Payslip exported

---

# 18. Employee Self-Service

Employees can:

- View profile
- Update permitted information
- View employment
- View attendance
- Request attendance correction
- View leave
- Request leave
- View overtime
- Request overtime
- View payslips
- Download payslips
- View benefits
- View documents
- Request COE
- View announcements
- View training
- View performance
- Change password
- Manage MFA
- View notifications
- View active sessions

---

# 19. Manager Self-Service

Managers can:

- View team
- View team attendance
- Approve leave
- Approve overtime
- View schedules
- Conduct performance reviews
- Approve requests
- View team statistics

Do not automatically give managers salary access.

Create a separate permission such as:

```text
employees.salary.view
```

---

# 20. Compensation

Track:

- Salary
- Salary grade
- Salary band
- Allowances
- Bonuses
- Incentives
- Salary adjustments
- Promotions

Never overwrite historical salary.

---

# 21. Benefits

Support:

- SSS
- PhilHealth
- Pag-IBIG
- HMO
- Insurance
- Allowances
- Loans
- Retirement benefits

Track:

- Plan
- Eligibility
- Enrollment
- Employee contribution
- Employer contribution
- Dependents
- Effective date
- End date

---

# 22. Performance Management

Functions:

- Performance cycles
- Goals
- KPIs
- Competencies
- Self-review
- Manager review
- Peer review
- Ratings
- Comments
- Performance history
- Performance Improvement Plan (PIP)

---

# 23. Training and Learning

Functions:

- Training catalog
- Training providers
- Courses
- Sessions
- Enrollment
- Attendance
- Cost
- Certificates
- Skills
- Competencies
- Expiration reminders

---

# 24. Employee Documents

Examples:

- Employment contracts
- Government documents
- Certificates
- HR forms
- Training certificates
- Performance documents
- Clearance
- Resignation documents

Use private storage for sensitive documents.

Do not expose sensitive employee files through public storage URLs.

---

# 25. Certificate of Employment

Employee:

```text
Request COE
 ↓
HR Approval
 ↓
Generate PDF
 ↓
Available in Portal
```

Support:

- Standard COE
- COE with compensation
- COE without compensation
- Employment verification

---

# 26. Offboarding

```text
Resignation
 ↓
Approval
 ↓
Notice Period
 ↓
Clearance
 ↓
Asset Return
 ↓
Final Payroll
 ↓
Final Pay
 ↓
COE
 ↓
Account Disable
 ↓
Separated
```

Never delete the employee record.

---

# 27. Workflow Engine

Recommended because multiple HR processes require approval.

Recommended tables:

```text
workflow_definitions
workflow_steps
workflow_instances
workflow_instance_steps
workflow_actions
workflow_comments
```

Support workflows for:

```text
Leave
Overtime
Salary Adjustment
Promotion
COE
Employee Information Change
Document Request
Training Request
```

---

# 28. Notifications

Channels:

- In-app
- Email
- Optional SMS

Events:

- Leave approved
- Leave rejected
- Payslip published
- Payroll finalized
- COE ready
- Training reminder
- Contract expiration
- Probation expiration
- Certification expiration
- Birthday
- Anniversary
- Pending approval

---

# 29. RBAC

Use:

```text
User
 ↓
Role
 ↓
Permission
 ↓
Module
 ↓
Action
 ↓
Data Scope
```

Avoid relying on a simple:

```text
is_admin = true
```

authorization model.

---

# 30. Superadmin

Required behavior:

- Cannot be deleted
- Cannot be disabled
- Cannot be demoted
- Cannot lose Superadmin privileges
- Cannot have the Superadmin role removed
- Protected from ordinary administrative modification
- Mandatory MFA
- All actions audited

Recommended fields:

```text
users.is_system_account
users.is_protected
```

---

# 31. Superadmin Sub-users

Superadmin can:

- Create users
- Edit users
- Disable users
- Assign roles
- Remove roles
- Reset password
- Force logout
- Force password reset
- Enable MFA
- Manage appropriate user security settings
- View login history

---

# 32. Recommended Default Roles

## Superadmin

Full system access.

## HR Administrator

Core HR + employee management.

## HR Staff

Operational HR functions.

## Payroll Administrator

Payroll and compensation.

## Attendance Officer

Attendance and schedules.

## Recruitment Officer

Recruitment and onboarding.

## Training Officer

Training and learning.

## Performance Officer

Performance management.

## Manager

Team-level access.

## Employee

Self-service only.

---

# 33. Permissions

Use granular permissions.

Examples:

```text
employees.view
employees.create
employees.update
employees.archive
employees.export

payroll.view
payroll.create
payroll.process
payroll.approve
payroll.finalize
payroll.lock
payroll.export

payslips.view
payslips.download

leave.view
leave.create
leave.approve
leave.reject

users.view
users.create
users.update
users.disable

roles.view
roles.create
roles.update
roles.delete
roles.assign
```

---

# 34. Data-Level Authorization

A user may have:

```text
employees.view
```

without having access to every employee.

Example:

```text
Manager
 ↓
Department = IT
 ↓
Can view IT employees
```

Possible scopes:

- Own record
- Own team
- Own department
- Own branch
- Own company
- All records

---

# 35. Recommended Database Structure

## Authentication

```text
users
roles
permissions
role_user
permission_role
user_sessions
login_attempts
password_reset_tokens
two_factor_authentications
```

## Organization

```text
companies
branches
locations
divisions
departments
sections
teams
positions
job_levels
job_grades
cost_centers
```

## Employees

```text
employees
employee_personal_details
employee_addresses
employee_contacts
employee_emergency_contacts
employee_government_ids
employee_bank_accounts
employee_dependents
employee_documents
employee_notes
```

## Employment

```text
employment_records
employment_types
employment_statuses
employee_positions
employee_department_assignments
employee_managers
employee_work_locations
employee_contracts
employee_probations
employee_separations
```

## Compensation

```text
salary_structures
salary_grades
salary_bands
compensation_types
employee_compensations
employee_compensation_items
allowance_types
bonus_types
salary_adjustments
```

## Attendance

```text
attendance_logs
attendance_sources
attendance_adjustments
attendance_summary
work_schedules
schedule_templates
employee_schedules
shift_definitions
shift_breaks
overtime_requests
overtime_records
holiday_calendar
holiday_types
```

## Leave

```text
leave_types
leave_policies
leave_policy_rules
employee_leave_balances
leave_accruals
leave_requests
leave_request_approvals
leave_adjustments
```

## Payroll

```text
payroll_groups
payroll_periods
payroll_runs
payroll_run_employees
payroll_earnings
payroll_deductions
payroll_contributions
payroll_taxes
payroll_adjustments
payslips
payslip_items
payroll_approvals
payroll_locks
```

## Recruitment

```text
job_requisitions
job_postings
applicants
applicant_documents
applicant_sources
applications
interviews
interviewers
interview_evaluations
assessments
job_offers
recruitment_statuses
```

## Onboarding

```text
onboarding_templates
onboarding_tasks
employee_onboarding
employee_onboarding_tasks
```

## Performance

```text
performance_cycles
performance_goals
performance_goal_progress
performance_reviews
performance_reviewers
performance_ratings
performance_comments
competencies
competency_levels
employee_competencies
```

## Training

```text
training_courses
training_categories
training_providers
training_sessions
employee_trainings
training_attendance
training_certificates
skills
skill_levels
employee_skills
```

## Benefits

```text
benefit_types
benefit_plans
benefit_plan_rules
employee_benefits
benefit_enrollments
benefit_transactions
```

## Workflow

```text
workflow_definitions
workflow_steps
workflow_instances
workflow_instance_steps
workflow_actions
workflow_comments
```

## Notifications

```text
notifications
notification_templates
notification_preferences
```

## Security

```text
audit_logs
security_events
login_logs
data_access_logs
```

## System

```text
system_settings
system_modules
system_features
file_uploads
announcements
announcement_targets
```

---

# 36. Database Design Rules

Use:

- Primary keys
- Foreign keys
- Unique constraints
- Proper indexes
- Check constraints where appropriate
- Transactions
- Soft deletes where appropriate
- Effective dates
- Historical records
- Consistent naming conventions

---

# 37. Important Indexes

Recommended indexes include:

```text
employees.employee_no
employees.status
employees.department_id

users.email

attendance_logs.employee_id
attendance_logs.attendance_date

leave_requests.employee_id
leave_requests.status

payroll_runs.payroll_period_id
payroll_run_employees.employee_id

payslips.employee_id
payslips.payroll_run_id

audit_logs.user_id
audit_logs.created_at
```

Add composite indexes based on actual query patterns.

---

# 38. Payroll Snapshot

When payroll is finalized, preserve the exact payroll state.

```text
employee
salary
earnings
deductions
contributions
tax
net pay
```

If salary changes next month, the previous month's payslip must remain unchanged.

---

# 39. Government Rules

Do not hard-code government contribution rates and tax values directly into application logic.

Recommended:

```text
government_agencies
government_rules
government_rule_versions
government_contribution_rates
tax_tables
tax_table_brackets
```

Use:

```text
effective_from
effective_to
```

This allows rule changes without rewriting payroll code.

---

# 40. Multi-Company Support

Design for multi-company operation where appropriate.

Example:

```text
Company A
 ├── Manila
 └── Bulacan

Company B
 ├── Manila
 └── Cebu
```

Relevant records may include:

```text
company_id
branch_id
```

Use data scopes to prevent cross-company access.

---

# 41. Employee Portal Sidebar

```text
MY HR

Dashboard

My Profile
My Employment

Attendance
  My Attendance
  My Schedule
  My Overtime

Leave
  My Leave
  Leave Request

Payroll
  My Payslips
  My Compensation
  My Benefits

Documents
  My Documents
  Request COE

Performance
Training

Requests

Announcements

Notifications

Account
  Security
  Sessions
```

---

# 42. Admin Sidebar

```text
HRIS

Dashboard

WORKFORCE
  Employees
  Organization
  Positions
  Employment
  Documents

TIME & ATTENDANCE
  Attendance
  Schedules
  Shifts
  Overtime
  Holidays
  Leave

PAYROLL
  Payroll
  Payroll Periods
  Compensation
  Benefits
  Payslips

TALENT
  Recruitment
  Applicants
  Onboarding
  Performance
  Training
  Skills
  Career

REPORTS
  HR Reports
  Attendance Reports
  Leave Reports
  Payroll Reports
  Analytics

ADMINISTRATION
  Users
  Roles
  Permissions
  Workflows
  Notifications
  Announcements
  Audit Logs
  Security
  Settings
```

---

# 43. Dark Mode

Use Bootstrap 5.3+ color mode support.

Support:

```text
Light
Dark
System
```

Do not create a completely separate theme framework unless necessary.

---

# 44. Laravel Architecture

Recommended structure:

```text
app/
├── Actions/
├── Console/
├── Domain/
│   ├── Employee/
│   ├── Attendance/
│   ├── Leave/
│   ├── Payroll/
│   ├── Recruitment/
│   ├── Performance/
│   ├── Training/
│   ├── Benefits/
│   ├── Workflow/
│   └── Security/
├── Enums/
├── Events/
├── Exceptions/
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   └── Requests/
├── Jobs/
├── Models/
├── Notifications/
├── Policies/
├── Services/
└── Support/
```

---

# 45. Keep Payroll Logic Out of Controllers

Avoid:

```php
public function calculatePayroll()
{
    // Hundreds of lines of payroll calculation
}
```

Prefer:

```text
PayrollController
       ↓
PayrollService
       ↓
PayrollCalculationService
       ↓
TaxService
       ↓
ContributionService
       ↓
PayslipService
```

---

# 46. Queue Processing

Use Laravel queues for:

- Payroll processing
- Payslip generation
- PDF generation
- Email
- Bulk notifications
- Reports
- Large exports
- Other heavy background processing

Example:

```text
Finalize Payroll
      ↓
Queue
      ↓
Process Employees
      ↓
Generate Payslips
      ↓
Publish
      ↓
Notify Employees
```

---

# 47. Scheduler

Automate:

- Leave accrual
- Contract expiration reminders
- Probation reminders
- Certification reminders
- Birthday notifications
- Anniversary notifications
- Payroll reminders
- Attendance processing
- Cleanup jobs
- Backup verification

---

# 48. Security Architecture

Security is a cross-cutting layer.

```text
                  SECURITY
                     │
      ┌──────────────┼──────────────┐
      │              │              │
 Authentication   Authorization   Audit
      │              │              │
      └──────────────┼──────────────┘
                     │
                    HRIS
                     │
        ┌────────────┼────────────┐
        │            │            │
       HR         Payroll     Employee Portal
```

---

# 49. OWASP Security Baseline

Use **OWASP Top 10:2025** as the security baseline.

Current categories:

1. A01 — Broken Access Control
2. A02 — Security Misconfiguration
3. A03 — Software Supply Chain Failures
4. A04 — Cryptographic Failures
5. A05 — Injection
6. A06 — Insecure Design
7. A07 — Authentication Failures
8. A08 — Software or Data Integrity Failures
9. A09 — Security Logging & Alerting Failures
10. A10 — Mishandling of Exceptional Conditions

OWASP Top 10 is the baseline; it is not itself a software package or library to install.

---

# 50. OWASP ASVS

Use **OWASP ASVS 5.0** as the detailed security verification checklist.

Recommended model:

```text
OWASP Top 10:2025
        ↓
Security Baseline

OWASP ASVS 5.0
        ↓
Detailed Security Requirements
        ↓
Implementation
        ↓
Testing
        ↓
Pass / Fail
        ↓
Evidence
```

---

# 51. Phase 17 — Security Hardening & OWASP Verification

## 17.1 Authentication

- [ ] Secure login
- [ ] Secure password hashing
- [ ] Password reset
- [ ] Login throttling
- [ ] Brute-force protection
- [ ] Generic authentication errors
- [ ] Account status checks
- [ ] MFA
- [ ] Recovery codes
- [ ] Session invalidation
- [ ] Force logout
- [ ] Password change
- [ ] Re-authentication for sensitive operations

---

## 17.2 Superadmin Security

- [ ] Superadmin cannot be deleted
- [ ] Superadmin cannot be disabled
- [ ] Superadmin cannot be demoted
- [ ] Superadmin role cannot be removed
- [ ] Superadmin MFA mandatory
- [ ] Sensitive operations require re-authentication/MFA
- [ ] All Superadmin actions audited
- [ ] Superadmin login alerts
- [ ] Superadmin session timeout
- [ ] Superadmin session management

---

## 17.3 Authorization

- [ ] RBAC
- [ ] Permissions
- [ ] Module permissions
- [ ] CRUD permissions
- [ ] Action permissions
- [ ] Laravel Policies
- [ ] Route authorization
- [ ] Controller authorization
- [ ] Service-layer authorization
- [ ] Data-level authorization
- [ ] Department scope
- [ ] Branch scope
- [ ] Company scope
- [ ] Manager scope
- [ ] Employee self-access

---

## 17.4 Broken Access Control Testing

Test:

```text
Employee → Other Employee
Employee → Other Payslip
Employee → Payroll
Employee → HR Documents
Manager → Other Department
HR Staff → Security Settings
Payroll Staff → User Administration
```

Every unauthorized attempt must fail.

This directly addresses OWASP A01:2025 Broken Access Control.

---

## 17.5 IDOR / Object-Level Authorization

Test URLs such as:

```text
/employees/100
/payslips/100
/documents/100
/payroll/100
/leave/100
```

Change IDs manually.

Expected:

```text
Authorized → 200
Unauthorized → 403/404
```

Never expose another employee's information.

---

## 17.6 Input Security

Test:

- SQL injection
- XSS
- HTML injection
- Command injection
- File path traversal
- Malicious parameters
- Invalid IDs
- Oversized requests
- Unexpected data types

Use Laravel validation and parameterized queries.

---

## 17.7 File Upload Security

For employee documents:

- [ ] MIME validation
- [ ] Extension validation
- [ ] File size limits
- [ ] Random filenames
- [ ] Private storage
- [ ] Authorization before download
- [ ] Virus/malware scanning where practical
- [ ] No executable uploads
- [ ] Content-type validation
- [ ] Download authorization
- [ ] Download logging

---

## 17.8 Session Security

- [ ] Secure cookies
- [ ] HttpOnly
- [ ] SameSite
- [ ] HTTPS
- [ ] Session regeneration
- [ ] Idle timeout
- [ ] Absolute timeout where appropriate
- [ ] Logout
- [ ] Logout all sessions
- [ ] Device/session list

---

## 17.9 CSRF

Verify CSRF protection for:

- Forms
- State-changing requests
- Admin actions
- Employee requests
- Payroll actions

Especially:

```text
POST
PUT
PATCH
DELETE
```

---

## 17.10 XSS

Test:

- Employee names
- Comments
- Announcements
- Notes
- Training descriptions
- Job postings
- Performance comments

Never blindly render user-supplied HTML.

---

## 17.11 SQL Injection

Test:

- Search
- Filters
- Sorting
- Reports
- Exports
- Employee IDs
- Payroll queries
- API parameters

Use Eloquent/query builder safely.

Be especially careful with dynamic SQL identifiers such as sorting fields. Use allow-lists.

---

## 17.12 Cryptography

Protect sensitive information appropriately.

Potentially sensitive:

- Bank account information
- Government IDs
- Tax information
- Salary
- Documents
- Authentication secrets

Never store passwords in plaintext.

Use Laravel's password hashing.

For highly sensitive application fields, evaluate Laravel encryption.

---

## 17.13 HTTPS

Production:

```text
HTTP
 ↓
HTTPS
```

Implement:

- TLS
- Secure cookies
- HSTS where appropriate
- HTTP → HTTPS redirect
- Secure headers

---

## 17.14 Security Headers

Review:

- Content-Security-Policy
- X-Content-Type-Options
- Referrer-Policy
- Permissions-Policy
- Frame protections
- HSTS

Do not blindly copy a CSP from another application. Tune it for the actual frontend resources.

---

## 17.15 Logging

Log:

- Login
- Failed login
- Logout
- Password changes
- MFA changes
- User creation
- User disabling
- Role changes
- Permission changes
- Salary changes
- Payroll changes
- Payroll finalization
- Payslip access
- Document downloads
- Data exports
- Security events

---

## 17.16 Audit Logs

Example:

```text
User:
John Smith

Action:
UPDATE

Module:
Employee Compensation

Employee:
EMP-00125

Before:
₱30,000

After:
₱35,000

IP:
...

Timestamp:
...
```

Audit logs should be protected from ordinary modification and deletion.

---

## 17.17 Data Access Logs

For particularly sensitive data, also log access.

Example:

```text
Payroll Administrator
VIEW
Employee: EMP-00125
Payslip: August 2026
```

---

## 17.18 Error Handling

Never expose in production:

```text
SQL errors
Stack traces
File paths
Environment variables
Database credentials
Internal implementation details
```

Use safe production error pages.

---

## 17.19 Production Configuration

- [ ] `APP_DEBUG=false`
- [ ] Secure `.env`
- [ ] Strong `APP_KEY`
- [ ] Database credentials protected
- [ ] Secrets not committed to Git
- [ ] Production logging configured
- [ ] HTTPS
- [ ] Secure cookies
- [ ] Error monitoring
- [ ] Queue workers protected
- [ ] Cron configured
- [ ] Database user least privilege

---

## 17.20 Dependency Security

Because software supply chain security is part of OWASP Top 10:2025:

- [ ] Composer audit
- [ ] NPM audit
- [ ] Dependency updates
- [ ] Remove unused packages
- [ ] Lock dependency versions
- [ ] Review third-party packages
- [ ] Review Laravel packages
- [ ] Monitor vulnerabilities
- [ ] Secure CI/CD pipeline
- [ ] Protect deployment credentials

---

## 17.21 Database Security

- [ ] Dedicated application database user
- [ ] Least privilege
- [ ] Never use root DB account in application
- [ ] Strong database password
- [ ] Network restrictions
- [ ] Encryption where appropriate
- [ ] Backups
- [ ] Backup encryption
- [ ] Restore testing
- [ ] Database access logging
- [ ] Migration control

---

## 17.22 Backup & Disaster Recovery

Implement:

```text
Database Backup
+
File Backup
+
Configuration Backup
```

Test:

```text
Backup
 ↓
Restore
 ↓
Validate
 ↓
Application Works
```

A backup that has never been restored is not a proven backup.

---

## 17.23 Payroll Security

Use segregation of duties / maker-checker.

```text
Payroll Staff
      ↓
Prepare

Payroll Reviewer
      ↓
Review

Payroll Approver
      ↓
Approve

System
      ↓
Finalize

System
      ↓
Lock
```

---

## 17.24 Sensitive Operations

Require additional authorization for:

- Salary changes
- Payroll finalization
- Payroll reversal
- User creation
- Role changes
- Permission changes
- Superadmin changes
- Government data changes
- Bank account changes
- Mass employee updates
- Bulk exports

Potentially require:

```text
Password Re-entry
+
MFA
```

---

## 17.25 OWASP Top 10:2025 Verification Matrix

| OWASP | HRIS Test |
|---|---|
| A01 Broken Access Control | Employee cannot view another employee's payslip |
| A02 Security Misconfiguration | Production debug disabled |
| A03 Supply Chain | Composer/NPM dependencies audited |
| A04 Cryptographic Failures | Sensitive data protected |
| A05 Injection | SQL/XSS/input testing |
| A06 Insecure Design | Payroll and authorization threat modeling |
| A07 Authentication Failures | MFA, throttling, sessions |
| A08 Data Integrity | Payroll approval/finalization controls |
| A09 Logging Failures | Sensitive actions audited |
| A10 Exceptional Conditions | Safe error handling and transactions |

---

# 52. OWASP ASVS 5.0 Verification

Create a security test matrix:

```text
ASVS Requirement
      ↓
HRIS Requirement
      ↓
Implementation
      ↓
Test
      ↓
Pass / Fail
      ↓
Evidence
```

Example:

```text
ASVS
Authorization

HRIS:
Employee can only access own payslip.

Test:
Change payslip ID.

Expected:
Access denied.

Result:
PASS
```

---

# 53. Phase 17 Final Security Checklist

## Authentication

- [ ] Password hashing
- [ ] MFA
- [ ] Password reset
- [ ] Login throttling
- [ ] Brute-force protection
- [ ] Session security
- [ ] Account lock/disable
- [ ] Re-authentication

## Authorization

- [ ] RBAC
- [ ] Permissions
- [ ] Policies
- [ ] Data-level authorization
- [ ] Company scope
- [ ] Branch scope
- [ ] Department scope
- [ ] Manager scope
- [ ] Employee self-access

## Application

- [ ] CSRF
- [ ] XSS
- [ ] SQL injection
- [ ] Input validation
- [ ] Output encoding
- [ ] File security
- [ ] Path traversal protection
- [ ] Secure error handling

## Data

- [ ] Encryption
- [ ] Sensitive data protection
- [ ] Private files
- [ ] Secure database
- [ ] Payroll snapshots
- [ ] Historical records

## Infrastructure

- [ ] HTTPS
- [ ] Security headers
- [ ] Firewall
- [ ] Secure server
- [ ] Database restrictions
- [ ] Secrets management
- [ ] Production configuration

## Monitoring

- [ ] Audit logs
- [ ] Security events
- [ ] Login logs
- [ ] Data access logs
- [ ] Alerts
- [ ] Error monitoring

## Supply Chain

- [ ] Composer audit
- [ ] NPM audit
- [ ] Dependency updates
- [ ] Package review
- [ ] CI/CD security

## OWASP

- [ ] OWASP Top 10:2025
- [ ] OWASP ASVS 5.0
- [ ] OWASP Authentication guidance
- [ ] OWASP Session guidance
- [ ] OWASP Authorization guidance
- [ ] OWASP File Upload guidance
- [ ] OWASP Logging guidance
- [ ] OWASP Secure Headers guidance

## Testing

- [ ] Unit tests
- [ ] Feature tests
- [ ] Authorization tests
- [ ] Payroll calculation tests
- [ ] Integration tests
- [ ] Security tests
- [ ] Penetration testing
- [ ] Vulnerability scanning
- [ ] Backup restoration test

---

# 54. Development Phases

## Phase 1 — Project Foundation

- Laravel setup
- MySQL setup
- Vite
- Bootstrap
- Environment configuration
- Base architecture
- Git repository
- Coding standards

## Phase 2 — UI/UX Foundation

- Sidebar
- Admin layout
- Employee layout
- Manager layout
- Responsive layout
- Dark mode
- Bootstrap components
- Tables
- Forms
- Modals
- Alerts
- Breadcrumbs
- Loading states
- Empty states
- Error states

## Phase 3 — Authentication

- Login
- Logout
- Password reset
- Password change
- MFA
- Sessions
- Account status
- Login throttling
- Security notifications

## Phase 4 — RBAC & Authorization

- Users
- Roles
- Permissions
- Role assignment
- Permission management
- Policies
- Data scopes
- Superadmin protection

## Phase 5 — Organization

- Companies
- Branches
- Locations
- Divisions
- Departments
- Sections
- Teams
- Positions
- Job levels
- Job grades
- Cost centers

## Phase 6 — Employee Core HR

- Employee master
- Personal information
- Addresses
- Contacts
- Emergency contacts
- Government IDs
- Dependents
- Documents
- Notes

## Phase 7 — Employee Lifecycle

- Employment records
- Contracts
- Probation
- Regularization
- Promotion
- Transfer
- Salary changes
- Separation

## Phase 8 — Attendance & Scheduling

- Attendance
- Schedules
- Shifts
- Overtime
- Holidays
- Attendance corrections
- Attendance reports

## Phase 9 — Leave Management

- Leave types
- Leave policies
- Leave credits
- Accrual
- Requests
- Approvals
- Adjustments
- Leave calendar

## Phase 10 — Compensation

- Salary structures
- Salary grades
- Salary bands
- Allowances
- Bonuses
- Incentives
- Salary adjustments
- Salary history

## Phase 11 — Payroll Engine

- Payroll groups
- Payroll periods
- Payroll processing
- Earnings
- Deductions
- Contributions
- Tax
- Overtime
- Holiday pay
- Payroll adjustments
- Payroll validation

## Phase 12 — Payroll Approval & Digital Payslip

- Payroll review
- Payroll approval
- Payroll finalization
- Payroll locking
- Payslip generation
- PDF generation
- Digital payslip portal
- Payslip download
- Payslip access logging
- Employee notifications

## Phase 13 — Employee & Manager Self-Service

- Employee portal
- Manager portal
- Requests
- Notifications
- COE
- Attendance correction
- Leave requests
- Overtime requests
- Employee profile updates

## Phase 14 — Recruitment & Onboarding

- Job requisitions
- Job postings
- Applicants
- Applications
- Interviews
- Assessments
- Job offers
- Onboarding templates
- Onboarding tasks

## Phase 15 — Talent Management

- Performance
- Goals
- KPIs
- Competencies
- Skills
- Training
- Career development
- Succession planning

## Phase 16 — Benefits & Offboarding

- Benefits
- Benefit plans
- Enrollment
- Dependents
- Contributions
- Clearance
- Asset return
- Final pay
- Separation

## Phase 17 — Security Hardening & OWASP Verification

- OWASP Top 10:2025
- OWASP ASVS 5.0
- Authentication testing
- Authorization testing
- IDOR testing
- SQL injection testing
- XSS testing
- CSRF testing
- File upload testing
- Session testing
- Security headers
- Dependency scanning
- Audit log testing
- Penetration testing
- Vulnerability testing
- Production security review

## Phase 18 — Production, Backup & Disaster Recovery

- Production deployment
- HTTPS
- Nginx/PHP-FPM
- Queue workers
- Scheduler
- Monitoring
- Error monitoring
- Database backup
- File backup
- Backup encryption
- Restore testing
- Disaster recovery procedure
- CI/CD
- Deployment procedure
- Rollback procedure

---

# 55. Recommended MVP

Do not build all modules at once.

## HRIS V1

1. Authentication
2. Users
3. Roles
4. Permissions
5. Organization
6. Employees
7. Employment History
8. Documents
9. Attendance
10. Schedules
11. Leave
12. Compensation
13. Payroll
14. Digital Payslip
15. Employee Self-Service
16. Manager Self-Service
17. COE
18. Notifications
19. Reports
20. Audit Logs
21. Security

## HRIS V2

```text
Recruitment
Onboarding
Performance
Training
Benefits
Career
Succession
Advanced Analytics
```

---

# 56. Final Security Architecture

Security should be built from Phase 1 onward.

Phase 17 is the formal security verification and hardening phase.

```text
                  SECURITY
                     │
      ┌──────────────┼──────────────┐
      │              │              │
 Authentication   Authorization   Audit
      │              │              │
      └──────────────┼──────────────┘
                     │
                    HRIS
                     │
        ┌────────────┼────────────┐
        │            │            │
       HR         Payroll     Employee Portal
```

Recommended security standard:

```text
OWASP Top 10:2025
        ↓
Security Baseline

OWASP ASVS 5.0
        ↓
Detailed Verification Standard

Laravel Security Controls
        ↓
Implementation

Automated + Manual Security Testing
        ↓
Security Acceptance
```

---

# 57. Core Design Principles

1. Security by design
2. Least privilege
3. Deny by default
4. Role-based access control
5. Data-level authorization
6. Segregation of duties
7. Maker-checker for sensitive workflows
8. Immutable payroll snapshots
9. Historical employee records
10. Effective-dated HR rules
11. Private sensitive documents
12. Auditability
13. Configurable business rules
14. Transaction-safe payroll processing
15. No hard-coded government rates
16. No plaintext passwords
17. No sensitive data in public URLs where avoidable
18. No permanent deletion of legally/operationally important HR records
19. Security testing before production
20. Tested backups and disaster recovery

---

# 58. Key Acceptance Criteria

The HRIS should not be considered production-ready until:

- [ ] Employees can securely access their own portal
- [ ] Employees can view their own payslips
- [ ] Employees cannot access another employee's payslip
- [ ] Managers can only access permitted employees
- [ ] Payroll is protected by approval workflows
- [ ] Finalized payroll cannot be silently modified
- [ ] Superadmin is protected from deletion/demotion
- [ ] Roles and permissions are granular
- [ ] Sensitive actions are audited
- [ ] Sensitive documents are private
- [ ] Authentication is hardened
- [ ] MFA is available and enforced for privileged accounts
- [ ] OWASP Top 10:2025 review is complete
- [ ] OWASP ASVS verification is performed
- [ ] Security tests pass
- [ ] Dependencies are scanned
- [ ] Production configuration is hardened
- [ ] Backups are working
- [ ] Restore procedure has been tested
- [ ] Disaster recovery procedure exists
- [ ] Payroll calculations have automated tests
- [ ] Authorization has automated tests
- [ ] Production errors do not expose sensitive information

---

# 59. Recommended Project Order

Build the application in this order:

```text
Foundation
    ↓
UI/UX
    ↓
Authentication
    ↓
RBAC
    ↓
Organization
    ↓
Employees
    ↓
Employment
    ↓
Attendance
    ↓
Leave
    ↓
Compensation
    ↓
Payroll
    ↓
Payslip
    ↓
Employee Portal
    ↓
Manager Portal
    ↓
Workflows
    ↓
Reports
    ↓
Recruitment
    ↓
Performance
    ↓
Training
    ↓
Benefits
    ↓
Security Hardening
    ↓
Testing
    ↓
Production
```

The most critical parts of the system are **RBAC/data-level authorization, payroll integrity, digital payslip privacy, auditability, workflow approval, and security**. These should be designed before implementing the surrounding modules.
