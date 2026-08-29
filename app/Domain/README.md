# Domain layer

Business logic lives here, grouped by bounded context, not by technical
layer. Each subdomain will typically hold its own `Services/`, `Actions/`,
`DataTransferObjects/`, and other classes once that module is built —
`app/Services` and `app/Actions` at the app root are for logic that is
genuinely cross-cutting (spans multiple subdomains) rather than owned by one
of them.

- `Employee/` — employee master data, personal details, documents
- `Attendance/` — attendance, schedules, shifts, overtime, holidays
- `Leave/` — leave types, policies, credits, requests
- `Payroll/` — payroll runs, earnings, deductions, contributions, tax, payslips
- `Recruitment/` — requisitions, postings, applicants, onboarding
- `Performance/` — cycles, goals, reviews, competencies
- `Training/` — courses, sessions, enrollment, skills
- `Benefits/` — benefit plans, enrollment, contributions
- `Workflow/` — the generic approval-workflow engine used across modules
- `Security/` — RBAC, audit logging, data-scope enforcement

Controllers stay thin (`app/Http/Controllers`) and delegate to a domain
service. Keep payroll calculation logic in particular out of controllers —
see `docs/HRIS_Blueprint.md` §45.

Subdomains are currently empty placeholders; they'll fill in as each phase
in `docs/HRIS_Blueprint.md` §54 is built.
