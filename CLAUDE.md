# HRIS

A modular Human Resources Information System. The full functional and
security specification is `docs/HRIS_Blueprint.md` — read it before working
on any module; this file only summarizes conventions and status.

## Response Style

Be terse. No preamble, no restating the task, no explaining what you're
about to do. After actions, report only: what changed, key results/
errors, and next step if blocking. Skip step-by-step narration of tool
calls. Don't summarize unless asked.

- No filler phrases ("Let me...", "I'll now...", "Great, now I'll...").
  Just act.
- Show only changed code (diffs/snippets), not full files, unless asked.
- Don't list every file checked or step taken — only what changed and why.
- No emoji, no exclamation marks, no encouragement/praise.
- If a task has no issues, just say "Done" — skip recap.
- Ask only when truly blocked; otherwise pick the sensible default and
  note the assumption in one line.
- Batch multiple related edits into one message, not one message per file.
- Skip disclaimers/caveats unless directly relevant to a risk in this
  change.

## Stack

- Laravel 12, PHP 8.3+
- MySQL 8+ in staging/production; local/sandbox dev without a MySQL server
  falls back to SQLite (see `.env` vs `.env.example`)
- Redis for cache/queue in staging/production (`.env.example`); local
  fallback uses the `database` driver for both
- Blade + Bootstrap 5.3+ (Sass, `resources/sass/app.scss`) + Alpine.js,
  bundled with Vite
- Sessions use the `database` driver everywhere (including production) —
  intentional, not a fallback: the blueprint's "view active sessions /
  force logout" features (§18, §31) need queryable session rows
- Auth backend is Laravel Fortify, used headless (`config/fortify.php`,
  `app/Providers/FortifyServiceProvider.php`) — our own Blade views in
  `resources/views/auth/`, not Fortify's stub views or Jetstream/Livewire
- RBAC is `spatie/laravel-permission` (`config/permission.php`) — our own
  `App\Models\Role` subclass (see Authorization below), not the package's
  own views (it doesn't ship any) or its teams feature

No starter kit (Breeze/Jetstream) is installed. Employee records and
everything past Organization is unbuilt — see Status below.

## Architecture

```
app/
├── Actions/         cross-cutting single-purpose actions (+ Actions/Fortify — see Authentication below)
├── Domain/          business logic by bounded context — see app/Domain/README.md
│   ├── Employee/ Attendance/ Leave/ Payroll/ Recruitment/
│   ├── Performance/ Training/ Benefits/ Workflow/ Security/
├── Enums/
├── Events/
├── Http/Controllers/   thin — delegate to a Domain service
├── Jobs/            queued work (payroll processing, PDF/payslip generation, bulk email)
├── Listeners/        auth/security event listeners (login logging, security notifications)
├── Models/
├── Notifications/
├── Policies/         UserPolicy, RolePolicy — see Authorization below
├── Services/          cross-cutting services (not owned by one subdomain)
└── Support/
```

Rules that matter more here than in a typical CRUD app (from blueprint §57,
non-negotiable — don't relax these for convenience):

- **Never hard-delete** an employee, or any record with payroll/attendance/
  leave/benefits/government history. Archive or soft-delete instead.
- **Never overwrite** historical compensation or employment data — use
  effective-dated rows.
- **Payroll is immutable once finalized.** Corrections go through an
  adjustment/reversal/correction record, never an UPDATE on a finalized run.
- **Payroll logic never lives in controllers.** Controller → Service →
  Calculation/Tax/Contribution/Payslip services (§45).
- **No hard-coded government contribution rates or tax brackets.** They're
  versioned, effective-dated data (§39).
- Authorization is RBAC **plus** data scope (own record / team / department
  / branch / company / all) — a permission like `employees.view` never by
  itself implies access to every employee (§34). Never fall back to an
  `is_admin` boolean check — the one exception is Superadmin's
  `Gate::before()` bypass, and that's a role inside the RBAC system, not a
  substitute for it (see Authorization below).
- Every payslip/document access checks object-level ownership
  (`payslip.employee_id === auth()->user()->employee_id`) unless the actor
  holds an explicit payroll permission — this is the one most worth writing
  a regression test for (§17).
- Security is built continuously, not bolted on in Phase 17 — Phase 17 is
  verification of controls that should already exist.

## Status

**Done:**
- Phase 1 — Project foundation (Laravel 12 install, Bootstrap/Sass/Alpine
  via Vite, `Domain`-driven folder layout, Pint, env config)
- Phase 2 (partial) — admin layout shell: sidebar (desktop) / offcanvas
  sidebar (mobile), breadcrumbs, light/dark/system mode, a placeholder
  dashboard. No employee/manager portal layouts yet, no forms/modals/table
  component library beyond what Bootstrap provides out of the box.
- Phase 3 — Authentication: login, logout, password reset, password
  change, 2FA (TOTP + recovery codes), login throttling, account
  status (`users.disabled_at`), login/logout/failed-login logging
  (`login_logs`), active-sessions list + force logout, password
  confirmation for sensitive actions, security-alert emails. See
  Authentication below before touching any of this.
- Phase 4 — RBAC & Authorization: roles/permissions (`spatie/laravel-
  permission`), the 10 default roles seeded with permissions (§32/§33),
  Superadmin protection + mandatory MFA (§30, now enforced), a `data_scope`
  concept on every role (§34) whose *enforcement* mostly waits on Phase 5/6,
  and admin UI for Users/Roles/Permissions. See Authorization below.
- Phase 5 — Organization: the Company → Division → Department → Section →
  Team hierarchy plus Branch/Location and Positions/Job Levels/Job
  Grades/Cost Centers, full admin CRUD UI wired into the sidebar
  (WORKFORCE > Organization/Positions), gated by
  `organization.view`/`organization.manage`. See Organization below
  before touching any of this.
- Phase 6 — Employee Core HR: the `employees` master record (personal/
  biographical data only — no job/position assignment yet, see Employee
  below for why) plus addresses, contacts, emergency contacts, government
  IDs, dependents, documents, and notes, all managed from one tabbed
  profile page. Gated by `employees.view`/`employees.create`/
  `employees.update`/`employees.archive`. See Employee below.
- Phase 7 — Employee Lifecycle: `employments`, an append-only effective-
  dated table covering hire/promotion/transfer/salary change/
  regularization/separation, surfaced as an Employment History tab on
  the same profile page. See Employment below — this is what finally
  closes the Team/Department/Branch data-scope loop mentioned above.
- Phase 8 — Attendance & Scheduling: Holidays, Shifts, Schedules (plus
  effective-dated `employee_schedules` assignment), Attendance (manual
  entry + audit-logged corrections, gated by a separate
  `attendance.correct` permission from `attendance.manage`), Overtime
  requests with approve/reject, and a filterable attendance summary
  report. Direct approve/reject rather than routing through a generic
  workflow engine — Workflow (blueprint module 27/48) is a later,
  not-yet-built module. See Attendance below.
- Phase 9 — Leave Management: Leave Types/Policies (CRUD), an audit-
  ledger balance system (`leave_balances` cached totals backed by an
  append-only `leave_transactions` table), and Leave Requests with
  submit → approve/reject/cancel — balance is only touched on approval
  (and reversed on cancelling an approved request), never at submission,
  per the blueprint's own §12 workflow diagram. See Leave below — this
  is the first module with real business logic in `app/Domain/`, not
  just flat controllers.
- Phase 10 — Compensation: `SalaryStructure`/`SalaryGrade` (CRUD, a
  grade's min/mid/max range *is* its "salary band" — no separate band
  table), a `salary_grade_id` on `employments` alongside the
  `basic_salary` snapshot already there, and `CompensationItem`
  (allowances/bonuses/incentives — one flexible table, not three) on the
  employee profile's new Compensation tab. Salary adjustments,
  promotions, and salary history needed no new tables — Phase 7's
  `Employment` already covers all three (a `change_type=salary_change`
  row, `change_type=promotion` row, and the Employment History tab,
  respectively). See Compensation below, including how its permission
  gating had to be reused across two different existing groups since the
  seeded catalog has no `compensation.*` permissions of its own.
- Phase 11 — Payroll Engine: Government Rules (`ContributionRateTable`/
  `Bracket`, `TaxTable`/`Bracket`, versioned and effective-dated per
  §39), `PayrollGroup`/`PayrollPeriod` (the latter finally giving
  blueprint §15's 9-state machine a real column, though Phase 11 only
  ever writes `Draft`/`Processing`/`ForReview` to it), a
  `PayrollCalculationService` that computes `PayrollItem` + line +
  contribution records per employee per period (basic pay prorated by
  pay frequency, active `CompensationItem`s, government contributions
  and withholding tax from the versioned rate tables), and manual
  adjustment lines with non-blocking validation flags. Overtime pay and
  holiday pay peso amounts are a deliberate, documented gap (no rate-
  policy data to compute them from yet). See Payroll below before
  touching any of this — it consolidates several of blueprint's
  suggested tables the same way Compensation and Organization did.
- Phase 12 — Payroll Approval & Digital Payslip: the rest of the state
  machine (`ForReview` → `ForApproval` → `Approved` → `Finalized` →
  `Locked` → `Published`, following blueprint §14's lifecycle diagram
  order rather than §15's bare list order, which disagree on where
  Published sits), each transition a guarded action on
  `PayrollPeriodController`, reject-with-reason sending a period back to
  `ForReview` rather than `Draft`; a payslip PDF (`barryvdh/laravel-
  dompdf`, no separate `Payslip` tables — a `PayrollItem` already holds
  everything one needs) downloadable by admins from `Finalized` onward
  and by an employee, from the new minimal portal at `/portal`, only
  once their own period is `Published`; `users.employee_id` (the
  previously-missing link blueprint §17's ownership rule depends on);
  and `payslip_access_logs` plus a `PayslipPublished` notification. See
  Payroll Approval and Digital Payslip Portal below.
- Phase 13 — Employee & Manager Self-Service: read-only **My Profile** in
  the portal (bio overview, Employment History, Documents); self-service
  **My Leave**/**Leave Request**/**My Overtime** (submit for oneself
  only, cancel a leave request with the same balance-reversal logic as
  the admin side); **My Attendance** correction requests (employee
  proposes a correction, HR approves through the same audit-logged
  `AttendanceCorrectionService` a direct correction uses); **Request
  COE** (Certificate of Employment, four blueprint variants, approval
  freezes a snapshot of current Employment so a re-download never
  silently changes); Manager Self-Service, delivered as
  `roles.data_scope` finally being enforced (`DataScopeResolver`) on the
  existing admin Employee/Leave/Attendance/Overtime controllers rather
  than a separate manager UI; and a **Requests** aggregation view merging
  all four request types into one portal page. See Employee Self-Service
  below for the full set of decisions and the handful of bullets left
  genuinely unbuilt because their underlying module (Benefits,
  Performance, Training) doesn't exist yet.

- Phase 14 — Recruitment & Onboarding (complete): **Job Requisitions**
  (headcount request, `pending → approved/rejected`, same request-then-
  decide shape as Leave/Overtime); **Job Postings** (`draft → published
  → closed`, only creatable against an *approved* requisition);
  **Applicants** (a candidate-pool profile, deliberately not company-
  scoped, with a private resume upload); **Applications** (one
  applicant against one posting, a nine-stage pipeline status with two
  terminal exits); **Interviews**/**Assessments** (nested under
  Application, both reachable from a new application show page);
  **Job Offers** (nested under Application, `pending → accepted/
  declined/rescinded`) plus the **hiring-conversion** step where an
  accepted offer creates a real `Employee` + `Employment` row, closing
  the loop back into Phase 6/7; and **Onboarding** (`OnboardingTemplate`/
  `OnboardingTask` company-scoped checklist definitions, assigned
  per-hire as `EmployeeOnboarding`/`EmployeeOnboardingTask` snapshots
  with computed progress tracking, on a new Onboarding tab on the
  employee profile page). See Recruitment below for the full set of
  decisions.

- Phase 15 — Talent Management (complete): **Performance Cycles**
  (`Draft → Active → Closed`), **Performance Goals** (per-employee,
  nullable measurable target instead of a separate KPIs table),
  **Performance Reviews** (self/manager/peer, one table by `type`, rating
  + comments, `Draft → Submitted → Acknowledged`), and **Performance
  Improvement Plans** (reason/goals/period, optionally linked to a
  triggering review, `Active → Successful/Unsuccessful/Cancelled`) on
  the Performance tab; **Competencies + Skills** (company-scoped
  catalogs plus per-employee ratings on a Skills & Competencies tab);
  **Training Providers/Courses/Sessions** (a course's catalog data plus
  its scheduled instances, `Scheduled → Completed/Cancelled`) and
  **Training Enrollment/Attendance/Certificates** (one table, a session's
  roster with a combined outcome+certificate decision and enforced
  capacity), gated by `training.view`/`training.manage`; and **Career
  Development Plans + Succession Candidacies** (`Active →
  Achieved/Cancelled` plans plus position-readiness candidacies) on a
  Career & Succession tab, both reusing `performance.view`/
  `performance.manage`. See Talent Management below for the full set of
  decisions, including two functions (Career development, Succession
  planning) blueprint names but never actually specifies.
- Phase 16 (complete) — Benefits & Offboarding: **Benefits** (`BenefitPlan`
  catalog limited to the four types not already covered elsewhere --
  HMO/Insurance/Loan/Retirement -- plus effective-dated, append-only
  `BenefitEnrollment` reusing the existing `EmployeeDependent` table for
  coverage), gated by `benefits.view`/`benefits.manage`; and
  **Offboarding** (`OffboardingRequest`, one row per resignation, driven
  through blueprint §26's fixed 10-step pipeline by a single generic
  `advance()` action rather than ten guarded methods, plus a Cancel
  off-ramp), gated by `employees.view`/`employees.update`, with the one
  real integration point (Account Disabled → `users.disabled_at`) wired
  and the rest (final payroll, COE, separation) left as documented,
  deliberate gaps. See Benefits and Offboarding below.

- Phase 17 (complete) — Security Hardening & OWASP Verification:
  **Security headers** (a new `SecurityHeaders` middleware setting CSP/
  X-Content-Type-Options/X-Frame-Options/Referrer-Policy/Permissions-
  Policy/conditional HSTS on every response, deliberately tuned — not
  blindly strict — to this app's real inline-script/inline-style/Alpine
  usage), a **generic Audit Log** (`audit_logs` + `AuditLogger`,
  wired at eight sensitive mutation points named by blueprint §51
  17.24 — user creation/role changes/disable-enable, role permission
  changes, salary changes, payroll finalization — gated by the
  long-reserved `audit-logs.view` permission), a **Broken Access
  Control / IDOR test suite** (`BrokenAccessControlTest`, one file
  walking blueprint §51 17.4/17.5's named scenarios end to end; every
  assertion passed on the first run — confirmation, not a fix), and
  **input/file/CSRF verification plus closing documentation**
  (zero raw SQL and one, trusted, non-user-input raw Blade echo found
  repo-wide; file upload MIME/size validation confirmed by new
  regression tests; every POST form confirmed to carry `@csrf`;
  `composer audit`/`npm audit` both clean; an OWASP Top 10:2025
  verification matrix, representative ASVS 5.0 entries, and a final
  security checklist all written into this file). See Security
  Hardening below for what's genuinely done versus deliberately left
  to Phase 18 or a real deployment's own review (infrastructure
  hardening, CI/CD, monitoring integration, penetration testing,
  vulnerability scanning).

- Phase 18 (complete) — Production, Backup & Disaster Recovery:
  **Scheduler** (Laravel's scheduler wired up for the first time in
  this app; `leave:accrue`/`leave:carry-over` close the Leave accrual
  gap Phase 9 documented, `training:send-certificate-expiration-
  reminders` closes the Training gap Phase 15f documented), **real
  queued notifications** (`SecurityAlert`/`PayslipPublished`/
  `TrainingCertificateExpiring` now implement `ShouldQueue`, verified
  genuinely queued against the real `database` queue connection, not
  just interface-compliant), **encrypted backup + restore testing**
  (`backup:run`/`backup:restore`/`backup:verify-latest`, covering
  database + private files + `.env`, checksum-verified, restore
  ground-truthed against real application data end to end), and
  **production deployment config + a deployment/disaster-recovery
  runbook** (`deploy/{nginx,php-fpm,supervisor}/`,
  `.env.production.example`, `DEPLOYMENT.md` — CI/CD confirmed already
  in place since this project's first commit, not rebuilt). See
  Production, Backup & Disaster Recovery below.

**Blueprint §54's full phase list (Phase 1 through Phase 18) is
complete.** Every deliberate, documented gap named along the way —
each phase's own section above names its own — remains a real, sized
follow-up, not a silent omission; there is no separate "not started"
phase left to point to. Re-read the relevant phase section above (this
file is a summary, not a substitute) before extending any area further.

- Phase 19 (complete) — Reports & Analytics: blueprint §3 names eight
  report/analytics modules (items 53-60: HR, Payroll, Attendance, Leave,
  Recruitment, Performance, Training Reports, plus Workforce Analytics)
  and §55's own "V1 MVP" list names Reports as item 19 of 21 — but
  blueprint never gives Reports a numbered detail section (confirmed by
  grep before starting: of the modules named without one, only
  "Workflow Engine" also has none) and never assigns it to one of §54's
  Phase 1-18 despite listing it as V1-scoped, the same "named but never
  scheduled" gap Career Development/Succession Planning (Phase 15g)
  turned out to be. With §54's own list already fully built, this
  became the session's own continuation past it — numbered Phase 19 to
  keep the same phase-lettering convention (19a, 19b, ...) every other
  multi-slice phase used rather than leaving it unlabeled. Four slices,
  all eight report modules: (19a) a Reports landing page and an HR
  Report (headcount by department/employment type/status), gated by the
  long-reserved `reports.view` permission nothing had checked until
  now; (19b) a Payroll Report (cost/deduction/contribution/tax totals
  per period), gated by `payroll.view` instead — payroll data gets the
  module's own tighter permission rather than `reports.view`, unlike
  HR's report; (19c) Recruitment (application-status funnel),
  Performance (average rating/goal completion per cycle), and Training
  (enrollment/completion/certificate) Reports, each likewise gated by
  its own module's `.view` permission, reachable only from the Reports
  landing page since blueprint's own admin nav sketch never gives them
  a sidebar row of their own; (19d) Workforce Analytics, a glance-level
  page combining one top-line number from each other report, closing
  out the sidebar's REPORTS section and the landing page's eight-card
  grid. A real cross-controller bug (19b's and 19c's period/cycle
  pickers both non-deterministic on a tied `start_date`) was caught and
  fixed in 19c. See Reports below.

## Authentication

Backend is Laravel Fortify (`laravel/fortify`), used **headless** — we
supply every view (`resources/views/auth/*.blade.php`,
`resources/views/layouts/guest.blade.php`) via `Fortify::loginView()` etc.
in `FortifyServiceProvider`; Fortify only owns the routes/controllers/
validation. Enabled features: `resetPasswords`, `updatePasswords`,
`twoFactorAuthentication` (confirm + confirmPassword required).
Registration and profile-info features are off — accounts are
admin-provisioned (§31), not self-registered; that provisioning UI is
Phase 4's job, so for now `database/seeders/DatabaseSeeder.php` creates
one dev user (`admin@example.test` / see the seeder for the password;
never runs in production automatically).

Non-obvious things worth knowing before changing this area:

- **`config('fortify.limiters.login')` is `null` on purpose.** Fortify's
  own internal pipeline (`EnsureLoginIsNotThrottled` /
  `LoginRateLimiter`) already throttles failed attempts to 5/minute per
  email+IP with a friendly inline error. A `throttle:login` *route*
  middleware runs *before* that pipeline step and fails closed with a
  raw unstyled 429 page instead — don't re-add a named `login` limiter.
- **`Auth::logoutOtherDevices()` is a no-op without `AuthenticateSession`
  middleware** on protected routes (it only rehashes the password; some
  middleware has to actually compare hashes per-request to catch other
  sessions). That's the `auth.session` alias in `bootstrap/app.php`,
  applied alongside `auth` in `routes/web.php` and `routes/auth.php`.
- **Password policy is length + composition (`AppServiceProvider`), not
  `Password::uncompromised()`** — deliberately not calling out to
  api.pwnedpasswords.com, since an HRIS is often deployed behind a
  locked-down corporate/on-prem network that wouldn't reach it.
- Fortify's 2FA/password-update error messages live in **named error
  bags** (`updatePassword`, `confirmTwoFactorAuthentication`) — use
  `@error('field', 'bagName')` in Blade, not the default bag.
- Security-relevant events (`PasswordReset`, `PasswordUpdatedViaController`,
  `TwoFactorAuthenticationConfirmed`/`Disabled`) trigger an email via
  `App\Notifications\SecurityAlert`; login/failed-login/logout are
  recorded to `login_logs` via listeners on Laravel's own auth events.
  Both sets of listeners are plain classes in `app/Listeners/`,
  auto-discovered — no manual `Event::listen()` wiring.

## Authorization

RBAC is `spatie/laravel-permission`. `config('permission.models.role')`
points at `App\Models\Role` (extends the package's own `Role` model to add
the `data_scope` cast) rather than the package's class directly — anywhere
you'd type-hint or import a Role, use `App\Models\Role`, not
`Spatie\Permission\Models\Role`; using the wrong one is why a policy or
cast silently won't apply. `App\Enums\DefaultRole` names the 10 seeded
roles (§32) — reach for it instead of a raw string wherever code needs to
know about one of them by name. The full permission catalog (§33, ~40
permissions) is seeded by `RoleAndPermissionSeeder`, mostly *reserved*
ahead of their module (e.g. `payroll.finalize` exists before Payroll does)
— a permission row existing doesn't mean anything's actually checking it
yet; each phase adds the `$this->authorize(...)` calls for its own
permissions as it's built, using the seeder's existing names rather than
inventing new ones.

**Superadmin** bypasses every permission check via `Gate::before()`
(`AppServiceProvider`) rather than being assigned every permission
explicitly — assigning all of them by hand would silently stop covering
permissions added by later phases. `User::isSuperadmin()` is the one place
that decides who this applies to. Protection (§30) is enforced two ways:
- `users.is_protected` (only the seeded account has it) blocks disable and
  Superadmin-role removal in `UserPolicy` — checked there, not skipped by
  the Gate bypass, because the bypass only fires for the *actor*, and
  these checks are about the *target*.
- Separately, `UserPolicy::removeSuperadminRole()` also blocks removing
  the role from whoever is currently the *last* Superadmin, protected or
  not — don't let the system reach zero Superadmins.
- MFA is mandatory for Superadmin: `EnsureSuperadminHasTwoFactorEnabled`
  (`mfa.superadmin` alias) redirects to `/security` until 2FA is
  confirmed. Applied to our own protected route groups AND to
  `config('fortify.middleware')` (so Fortify's own 2FA/password routes
  are covered too) — its `ALLOWED_ROUTES` allowlist is what keeps that
  from being a redirect loop.
- The Superadmin *role itself* can't be edited or deleted (`RolePolicy`)
  — its permission list is never shown in the UI, since its real power is
  the Gate bypass above, not that list.

**Data scope** (§34): `App\Enums\DataScope` (Own/Team/Department/Branch/
Company/All) lives on `roles.data_scope`, one value per role rather than
per permission grant — in practice a role's permissions share one reach,
and one column per role is far simpler to query than a scope on every row
of `role_has_permissions`. The seeded roles all have a real value (see
`RoleAndPermissionSeeder::ROLES`).

As of Phase 13e, `App\Domain\Security\Services\DataScopeResolver` closes
this gap for the two scope values a seeded role actually uses: **Own**
(the `Employee` self-service role) and **Team** (`Manager`, resolved via
`Employment.manager_id` — see `Employee::scopeReportingTo()`).
`employeeIdsFor($user, $permission)` returns `null` for "don't filter"
or a `list<int>` of employee IDs to restrict a query to; it resolves
scope against roles only — a permission granted directly to a user
(`givePermissionTo()` with no role, as several older tests' ad-hoc
"manager" test helpers do) carries no `data_scope` at all and is treated
as unrestricted, since data_scope is a property of a *role*, not a raw
permission grant. Applied so far to `Admin\EmployeeController`
(index/show — "View team"), `Admin\LeaveRequestController`
(index/approve/reject — "Approve leave"), `Admin\AttendanceController`
(index — "View team attendance"), and `Admin\OvertimeRequestController`
(index/approve/reject — "Approve overtime"). **Department/Branch/
Company/All stay unenforced on purpose** — no seeded role exercises them
today (every non-Manager, non-Employee role is Company-scoped, and nothing
queries Company scope either), and building general enforcement for
scopes nothing uses would be speculative, the same restraint applied to
`LeaveBalanceService`/`AttendanceCorrectionService`. Concretely: an HR
Administrator, HR Staff, Payroll Administrator, or Attendance Officer
sees exactly what they did before Phase 13e — only Manager (and, if an
Own-scoped role is ever also granted an admin-side permission, Employee)
is actually restricted today.

**Overtime approval needed a new permission, not a scope check alone.**
Admin approve/reject was `attendance.manage`-gated, which Manager doesn't
hold — granting it would also open shift/schedule/holiday CRUD and manual
attendance entry company-wide, well past blueprint §19's "Approve
overtime." Added `attendance.approve` (mirroring `leave.approve`'s
naming) instead, granted only to Manager; `OvertimeRequestController`'s
approve/reject accept *either* `attendance.manage` (unrestricted, as
before) or `attendance.approve` (Team-scope checked) — same two-tier
shape `attendance.correct`/`attendance.manage` already established in
Phase 8 for "a role can do the narrow thing without the broad thing."

When a Domain model needs a scope this resolver doesn't cover yet:
resolve the acting user's effective scope as the *broadest* among their
roles for that permission (a user with both a Team-scoped and a
Company-scoped role gets Company for actions either covers), then filter
the query accordingly — don't build a fake example against a model that
doesn't exist.

**A mass-assigned field silently missing from `$fillable` fails loudly
outside production** (`Model::preventSilentlyDiscardingAttributes()` in
`AppServiceProvider`) instead of the `update()` call quietly no-op'ing —
added after exactly that shape of bug shipped past manual testing once
already (`disable()`/`enable()` on `User` before `disabled_at` was
fillable). `is_system_account`/`is_protected` are deliberately kept off
`User::$fillable` even though `disabled_at` is on it — the one place that
sets them (`DatabaseSeeder`) goes through a factory, which bypasses
`$fillable` entirely, so they don't need to be, and leaving them off means
no stray `$user->update($request->all())` can ever grant Superadmin-style
protection to an arbitrary account.

## Organization

The hierarchy is `Company → Division → Department → Section → Team`, plus
`Branch`/`Location` (also directly under Company; `Location` optionally
belongs to a `Branch`) and the lookup entities `Position`, `JobLevel`,
`JobGrade`, `CostCenter`. Migrations: `2026_08_30_170001` through
`_170006` for the hierarchy, `_170101` through `_170104` for the lookups —
sequential on purpose, since `make:migration` giving several tables the same
timestamp sorts them alphabetically as a tiebreaker, which can put a child
table before the parent it has a foreign key to.

**Every level below Company carries its own direct `company_id`, not just a
link to its immediate parent.** Division/Department/Section/Team/Position/
JobLevel/JobGrade/CostCenter all have a nullable immediate-parent id
(`division_id`, `department_id`, `section_id`, `job_level_id`, ...) *and* a
required `company_id`. This is a deliberate deviation from a strict
5-level-chain-only model: it keeps `Employee::company_id` (Phase 6) a simple
direct column instead of a recursive walk up the chain, and it's what makes
the per-company scoping below possible without joining through every
intermediate level. A record's immediate-parent, when set, must belong to
the same `company_id` — enforced in every controller's validation via
`Rule::exists(...)->where('company_id', ...)`, not at the database level.

**Code uniqueness is scoped per company, not global** — two companies can
both have a `division` with code `HR`. Every controller enforces this with
`Rule::unique(...)->where('company_id', ...)->ignore($model?->id)`, backed
by a matching `unique(['company_id', 'code'])` composite index at the
migration level on every table below Company (Company itself has no
parent to scope by, so its `code` is globally unique instead).

**Deleting is blocked while children exist**, checked in each
`destroy()` before the (soft) delete — e.g. `CompanyController::destroy()`
checks 9 relations, `DivisionController::destroy()` checks `departments()`,
`JobLevelController::destroy()`/`JobGradeController::destroy()` check
`positions()`. `Team` and `Position` are leaves with nothing to check.
This is an application-level integrity rule on top of (not a replacement
for) the soft-delete + `nullOnDelete()` FK behavior — soft deletes don't
trigger `nullOnDelete`, so without this check a UI could hide a record
while other rows still silently pointed at it.

**The `is_active` checkbox default is context-sensitive — this exact
pattern is repeated in every controller in `app/Http/Controllers/Admin/`
for this module:** `$request->boolean('is_active', $model === null)`. An
unchecked HTML checkbox submits no field at all, so `'sometimes'` in the
validation rules would leave `is_active` untouched on update instead of
turning it off; reading it explicitly outside validation fixes that, but
then needs a fallback that differs by direction — absent means "use the
default" (true) on create, but "the box was there and got unchecked"
(false) on update.

**Route parameter names for the four kebab-case resources are
underscored, not hyphenated:** `Route::resource('job-levels', ...)`
produces `{job_level}` in the URI (Laravel dashes the URI segment but
underscores the wildcard), so `JobLevelController::update(Request
$request, JobLevel $jobLevel)` still resolves correctly via implicit
binding (Laravel snake-cases the parameter name before matching) —
but if you ever need to reference the raw route parameter yourself,
it's `job_level`/`job_grade`/`cost_center`, not `job-level`/etc.

Admin UI lives at `resources/views/admin/organization/*`, one directory
per entity, each with `_form-fields.blade.php` (shared between
create/edit) plus `index`/`create`/`edit`. `<x-admin.resource-index>`
(`resources/views/components/admin/resource-index.blade.php`) is the
shared list-page chrome (create button, flash/error alert, table shell)
used by every `index.blade.php` in the app, not just Organization.
`<x-admin.org-subnav>` is Organization-specific: a 10-item pill row
included at the top of every one of this module's index pages, since the
sidebar collapses all ten entities into two nav items (see below) and
the subnav is how the other eight stay reachable.

Sidebar wiring (`resources/views/layouts/partials/sidebar.blade.php`):
WORKFORCE > **Organization** links to Companies and stays highlighted
across all six hierarchy pages; WORKFORCE > **Positions** links to
Positions and stays highlighted across all four lookup pages. Both use
an `'active' => [...]` config entry (a list of `routeIs()` patterns)
rather than the single-route default the other nav items use — that's
what makes e.g. the Departments page still show "Organization" as
active instead of nothing being highlighted.

**No seeded role has `organization.manage`** — only `organization.view`
(HR Administrator, HR Staff). Superadmin can still create/edit/delete via
the `Gate::before()` bypass; any other role needs `organization.manage`
granted explicitly through the Phase 4 Roles UI before it can manage
organization data. This isn't a bug to fix here — it's the same
"permission exists, nothing's granted it by default yet" pattern the rest
of the seeder follows.

## Employee

`App\Models\Employee` is deliberately bio-data only — no `department_id`,
`position_id`, `branch_id`, or salary on the model itself. Blueprint §54
splits this across two phases on purpose: Phase 6 (this one) is "Employee
Core HR" (the person), Phase 7 "Employee Lifecycle" owns Employment as
its own effective-dated table (hire date, position, department, manager,
salary, status) so promotions/transfers/raises become new rows instead of
overwriting history (CLAUDE.md's "never overwrite historical employment
data" rule, non-negotiable). Don't add a "current department" convenience
column to Employee before Phase 7 exists to populate it correctly.

`Employee::company_id` is still direct and required, same as every
Organization entity — it's what makes Company-level data scope (§34)
enforceable starting now; Team/Department/Branch-level scope still needs
Phase 7's Employment table to know *where* in the org chart an employee
currently sits.

**Archiving, not soft-deleting, is the employee-facing lifecycle action**
(blueprint's CRUD list: "Create, Read, Update, Archive, Restore"). Mirrors
`users.disabled_at` from Phase 3: `employees.archived_at` toggled by
`archive()`/`restore()` in `EmployeeController`, both gated by the same
`employees.archive` permission. `SoftDeletes` is still on the model too,
but that's the Superadmin-only "undo an accidental create" escape hatch,
not a user-facing action — nothing in the UI exposes a delete/trash flow.

**Seven related tables, one profile page.** Addresses, contacts,
emergency contacts, government IDs, dependents, documents, and notes each
have their own model + nested controller
(`app/Http/Controllers/Admin/Employee*Controller.php`, routes in
`routes/employees.php` under `admin.employees.{addresses,contacts,...}`)
but no index/create/edit views of their own — they're managed entirely
from Bootstrap modals on `admin.employees.show`
(`resources/views/admin/employees/show/_*.blade.php`, one tab per
entity). Every nested controller checks
`$subResource->employee_id === $employee->id` before acting (404 if not)
since the sub-resource's own route-model-bound ID doesn't otherwise
guarantee it belongs to the `{employee}` in the URL.

**Addresses/contacts/emergency contacts have an `is_primary` flag that's
kept exclusive per employee** — saving one as primary un-sets it on the
others of that same type, done inside a `DB::transaction()` in each
controller's private `save()` helper (see
`EmployeeAddressController::save()` for the pattern). Government IDs,
dependents, documents, and notes don't have this concept.

**Employee documents are stored on the `local` disk, not `public`** —
`Storage::disk('local')` under `storage/app/private/employee-documents/
{employee_id}/`, deliberately not web-accessible by URL. Downloads go
through an authenticated `EmployeeDocumentController::download()` action
that checks `employees.view` before streaming the file, the same
object-level-access spirit as the payslip-ownership rule this file
already calls out as most worth protecting.

## Employment

`Employment` (table `employments`) is the effective-dated record Employee
deliberately doesn't carry (see Employee above). It is **append-only —
there is no `update()`/`destroy()` on `EmploymentController`, only
`store()`.** Every lifecycle event (hire, promotion, transfer, salary
change, regularization, separation — `App\Enums\EmploymentChangeType`)
inserts a new row; if a current row exists (`end_date IS NULL`),
`EmploymentController::store()` closes it first by setting its
`end_date` to the new row's `effective_date` minus one day, in the same
`DB::transaction()`. This is the same "never overwrite, only append"
principle as payroll immutability, applied one phase early because the
blueprint's own Employment History example (§6) shows position and
salary changing together as one timeline, not as separately-tracked
fields.

`Employee::currentEmployment` (a `hasOne` scoped to `whereNull('end_date')`)
is how every other part of the app should ask "where does this employee
work right now" — department, position, branch, location, manager,
salary. Don't add a denormalized "current department" column to
`Employee` as a shortcut; query through `currentEmployment` (or
`employments()->latest('effective_date')` for history) instead, so
there's exactly one source of truth for "current."

`department_id`/`position_id`/`branch_id`/`location_id`/`manager_id` on
an `Employment` row are all validated against the *employee's own*
`company_id` (`Rule::exists(...)->where('company_id', $employee->company_id)`)
— unlike Organization's controllers, the company itself isn't a form
field here, since an employee's company is fixed by their `employees`
row, not chosen per employment change. `manager_id` additionally rejects
an employee being their own manager, checked in the controller (not a
validation rule) since it needs the route's `$employee` for comparison.

## Attendance

Six entities under one module: `Holiday`, `Shift`, `Schedule` (company-
scoped CRUD, same pattern as Organization) plus `EmployeeSchedule`
(effective-dated, append-only assignment — identical shape to
`Employment`: `EmployeeScheduleController::store()` closes the prior
current row before inserting the new one), `Attendance` (one row per
employee per day), and `OvertimeRequest` (submit → approve/reject).
`<x-admin.attendance-subnav>` cross-links all six index pages the same
way `<x-admin.org-subnav>` does for Organization.

**`Attendance` is corrected in place, not appended — the one exception
to the effective-dated pattern used everywhere else in this app**, because
the unique constraint is `(employee_id, date)`: there's only one row an
employee can have for a given day, so there's nothing to append a new
row *to*. Instead, every correction is required to log the old/new value
of each changed field to `attendance_correction_logs` (with a reason and
who made it) *before* the row itself is updated — see
`AttendanceController::update()`. This is still "never overwrite
silently," just implemented differently than `Employment`'s "insert a
new row" because the data shape doesn't support that here. Corrections
require the separate `attendance.correct` permission, not
`attendance.manage` — a role can manage shift/schedule setup without
being able to alter recorded attendance, or vice versa.

**`late_minutes`/`undertime_minutes` are computed against the
employee's `currentSchedule->schedule->shift`** (start/end time +
grace_minutes) in `AttendanceController::computeMinutes()`, both on
manual entry and on every correction (so correcting a time recalculates
these rather than leaving stale values). If the employee has no current
schedule or shift, both are `0` — there's nothing to compare against.
One Carbon gotcha worth remembering here: **`diffInMinutes()` returns a
*signed* difference in Carbon 3** (unlike Carbon 2's always-absolute
default), so a naive `$timeIn->diffInMinutes($shiftStart)` comes back
negative when `$timeIn` is after `$shiftStart` — pass `true` for the
`$absolute` argument, as `computeMinutes()` does.

**Every `date`-typed column that's ever compared via `Rule::unique()`
uses an explicit `'date:Y-m-d'` cast, not the bare `'date'` cast** —
`Holiday`, `Attendance`, `OvertimeRequest` all do this. The bare `'date'`
cast reads back as a Carbon date but *writes* using the query grammar's
full `'Y-m-d H:i:s'` format, so a `Rule::unique()` check comparing raw
`'2026-01-01'` request input against a stored `'2026-01-01 00:00:00'`
silently never matches — a real bug caught by
`HolidayShiftScheduleTest::test_holiday_crud_and_date_uniqueness_per_company`
(it let a duplicate through validation, which the
database's own unique index then rejected as an unhandled exception
instead of a friendly form error). Match this cast whenever a new
date column needs uniqueness or exact-value validation.

**No generic workflow engine yet** — Overtime approve/reject are plain
`PUT` actions gated by `attendance.manage`, not routed through a
workflow_definitions-style engine (blueprint module 27/48, a later,
not-yet-built module). Don't build a bespoke approval chain here either;
when Workflow lands, this is a candidate to migrate onto it, not to
extend further with more ad-hoc states.

## Leave

Four entities: `LeaveType`/`LeavePolicy` (company-scoped CRUD, same
pattern as Organization/Attendance) and `LeaveRequest`/the balance
ledger (`LeaveBalance` + `LeaveTransaction`). `<x-admin.leave-subnav>`
cross-links Requests/Calendar/Leave Types/Policies/Report the same way
the other subnav components do.

**`LeaveBalance.balance` is a cache, never written to directly outside
`App\Domain\Leave\Services\LeaveBalanceService::applyTransaction()`.**
That's the one place that (a) locks the balance row, (b) writes the new
total, and (c) inserts the `LeaveTransaction` row explaining the change
— all in one `DB::transaction()`. Every caller (manual adjustment via
`LeaveBalanceController::adjust()`, request approval, cancellation
reversal) goes through it, so `leave_transactions` is always a complete,
correct audit trail of every balance change and its reason. This is the
first genuinely shared piece of business logic since Phase 6, and the
first real use of `app/Domain/` (previously just empty placeholders,
see `app/Domain/README.md`) — controllers stayed flat through Phases
5-8 because nothing needed the same logic from more than one call site;
Leave does, so it earns a service. Don't retroactively move Phase 5-8
logic into `app/Domain/` just for consistency — only reach for it when
a new module has the same shared-logic shape Leave does.

**Balance changes on approval, not on submission** — `LeaveRequestController::store()`
creates the request as `pending` and touches nothing else; only
`approve()` calls `applyTransaction()` (type `usage`, a negative
amount). This matches blueprint §12's own workflow diagram (Request →
Manager → HR → Approved → *Balance Updated*, in that order) rather than
optimistically reserving days at submission time. Cancelling a `pending`
request is a pure status change; cancelling an `approved` one calls
`applyTransaction()` again with type `reversal` and a *positive* amount
equal to `days_count` — the original `usage` transaction is never
edited or deleted, only offset by a new row, so the ledger still shows
both the deduction and its reversal.

**No accrual scheduler yet.** `LeavePolicy` stores the accrual rules
(rate, frequency, max balance, carry-over), but nothing runs them on a
cron — `LeaveTransactionType::Accrual` and `::CarryOver` exist in the
ledger's vocabulary for when that job is built, but today the only way
a balance actually changes is a manual `Adjustment` (via the employee
profile's Leave tab) or `Usage`/`Reversal` from request approval/
cancellation. Don't fake automatic accrual in the UI; document the gap
like this instead.

## Compensation

`SalaryStructure` (a named, dated compensation plan — e.g. "2026 Salary
Structure") contains `SalaryGrade`s, each with `min_salary`/
`mid_salary`/`max_salary`. Blueprint §20 lists "salary grades" and
"salary bands" as separate bullets, but a grade's band *is* its
min/mid/max range — a third table would just be `salary_bands.grade_id`
1:1 with nothing else on it, so it's collapsed into `SalaryGrade`
instead. `Employment` (Phase 7) gained a nullable `salary_grade_id`
(migration `2026_10_25_250003`, added to the existing table rather than
changing its original migration) alongside the `basic_salary` it already
had — an Employment row can reference *which* grade an employee is on
as well as their actual snapshot salary, which don't have to match
exactly (someone can be paid above or below their grade's band).

**Salary adjustments, promotions, and salary history are not new
tables** — they're exactly what `Employment`'s `change_type` values
(`salary_change`, `promotion`) and the Employment History tab already
provide (see Employment above). Don't build a parallel
"compensation history" table; a compensation change *is* a new
`Employment` row.

**`CompensationItem` is one table for allowances, bonuses, *and*
incentives** (a `type` enum), not three near-identical tables — they
only differ by category, not structure (name, amount, frequency,
effective/end date). Managed from the employee profile's Compensation
tab, same nested-controller-plus-modal pattern as the other per-employee
sub-resources (Phase 6).

**Permission gating is split across two existing groups, not a new
`compensation.*` one** — the seeded catalog (`RoleAndPermissionSeeder`)
has no compensation permissions at all, and CLAUDE.md's own rule is to
reuse existing names rather than invent new ones. `SalaryStructure`/
`SalaryGrade` reuse `organization.view`/`organization.manage` (they're
company-wide classification data, the same shape as `Position`/
`JobGrade` from Phase 5); `CompensationItem` reuses `employees.view`/
`employees.update` (it's a per-employee record, the same shape as
`Employment`/`EmployeeDocument`). If Payroll (Phase 11) needs a
dedicated `compensation.*` permission group, that's the natural point to
add one — don't add it speculatively here.

## Payroll

Phase 11 (Payroll Engine) is in progress, built in sub-slices; this
section grows as each lands. Unlike Compensation, Payroll *does* have its
own seeded permission group (`payroll.view`/`create`/`process`/`approve`/
`finalize`/`lock`/`export`, all on the "Payroll Administrator" role) — use
those rather than reusing Organization's or Employees', since the gap
that forced Compensation's workaround doesn't exist here.

**Government Rules (rate tables) — the first slice.** Blueprint §39's
6-table recommendation (agencies, rules, rule versions, contribution
rates, tax tables, tax brackets) is consolidated to 4 real tables plus one
enum, the same "collapse redundant layers" judgment call as Compensation's
salary-band decision:
- `App\Enums\GovernmentAgency` (SSS/PhilHealth/Pag-IBIG/BIR) — a fixed,
  small set with no fields of its own beyond a label, so it's an enum, not
  a CRUD'able `government_agencies` table. If a real deployment ever needs
  a fifth agency or per-agency metadata, promote it to a table then — not
  speculatively now.
- `ContributionRateTable` (header: company, agency, name, effective date
  range, active flag) + `ContributionRateBracket` (child: order, min/max
  salary, employee amount, employer amount) — one rate table's brackets
  are managed from its `show` page via add/edit/delete modals, the same
  pattern Phase 6 established for per-employee sub-resources (see Employee
  above), just keyed to a rate table instead of an employee.
- `TaxTable` + `TaxTableBracket` — identical shape, income brackets with a
  base tax plus an excess percentage instead of flat employee/employer
  amounts.
- Both header tables are versioned and effective-dated (`effective_from`/
  `effective_to`, nullable end = current), never edited over — CLAUDE.md's
  non-negotiable "no hard-coded government contribution rates or tax
  brackets" rule (§39) is about the *calculation code* in Phase 11c never
  literally containing a rate; it does not forbid a seeder or admin form
  from populating these tables with real numbers. There is deliberately no
  seeder for this slice, though — every other phase's "sample data" (test
  companies, employees, salary grades) has only ever been created ad hoc
  via `tinker` for manual/browser verification, never committed as a
  seeder, and inventing one here (especially one that would need
  plausible-looking PH SSS/PhilHealth/Pag-IBIG/BIR figures to be useful)
  risks those numbers being mistaken for real, current rates by whoever
  reads the repo later. Populate real rates through the admin UI when
  deploying for real; use factories (`ContributionRateTableFactory` etc.)
  or `tinker` for tests/local data.
- Sidebar: WORKFORCE's sibling **PAYROLL** section gained a real
  **Government Rates** item (gated by `payroll.view`) pointing at the
  contribution-rate-tables index; "Payroll" and "Payroll Periods" stay
  placeholders until Phase 11b.

**Payroll Groups + Periods — the second slice.** `PayrollGroup` (company,
name, code, `pay_frequency` enum, active flag) is a plain company-scoped
lookup, same shape and same code-uniqueness-per-company pattern as
`LeaveType`/`Holiday`. `PayrollPeriod` (company, `payroll_group_id`, name,
start/end/pay dates, `status`) is where blueprint §15's full 9-state
machine (`App\Enums\PayrollPeriodStatus`) first gets a real column to live
in — but Phase 11 only ever writes `Draft` to it; `Processing` through
`Cancelled` are reserved enum cases with no code that transitions into
them yet (same "the row/case exists before the phase that drives it"
pattern as the permission catalog and `LeaveTransactionType::Accrual`).
That single fact — nothing past `Draft` exists yet — is also *why* edit
and delete on a period both hard-block once `status !== Draft`
(checked in the controller, not just hidden in the UI): once Phase 11c
starts generating `PayrollItem` rows against a period, silently letting
its dates or group change out from under those rows would be exactly the
"overwrite instead of append/guard" mistake CLAUDE.md's payroll rules
exist to prevent, even though the guard has to live here, ahead of the
calculation engine that makes it matter.

**Two periods in the same payroll group can't have overlapping date
ranges** — enforced with a closure validation rule
(`PayrollPeriodController::noOverlapRule()`) rather than a `Rule::unique`
or a DB constraint, since "overlap" is a range comparison
(`start <= other.end AND end >= other.start`) that neither can express.
Cancelled periods are excluded from the check (a cancelled period
shouldn't block reusing its date range), and the rule ignores the period
being edited on update the same way `Rule::unique(...)->ignore()` does
elsewhere.

**Payroll Calculation Engine — the third slice.** `App\Domain\Payroll\
Services\PayrollCalculationService::process()` is the one place payroll
math happens (CLAUDE.md's "payroll logic never lives in controllers"
rule) — `PayrollPeriodController::process()` just resolves the period,
checks `payroll.process`, and calls it. Consolidates blueprint's
`payroll_runs`/`payroll_run_employees`/`payroll_earnings`/
`payroll_deductions` into three tables, the same collapsing judgment call
as Government Rules and Compensation:
- No separate `payroll_runs` table — a period already carries `status`,
  and recalculating a Draft/ForReview period is allowed to freely replace
  its numbers (nothing is finalized yet, so there's no history to
  protect), unlike the append-only pattern Employment/EmployeeSchedule
  use for data that genuinely must stay historical. `processed_at`/
  `processed_by` on `PayrollPeriod` itself is enough audit trail.
- `PayrollItem` (one row per employee per period: basic salary snapshot,
  gross earnings, contribution/tax/deduction totals, net pay) replaces
  `payroll_run_employees`.
- `PayrollItemLine` merges `payroll_earnings` and `payroll_deductions`
  into one table with a `type` enum (`PayrollItemLineType::Earning`/
  `Deduction`) plus a `category`/`label`, exactly `CompensationItem`'s
  "they only differ by category, not structure" reasoning. It also
  carries `is_adjustment`/`remarks`/`created_by` columns now, unused by
  this slice but there so Phase 11d's manual adjustments (task #55) are
  just rows on this same table instead of a fourth one — see the
  reprocessing note below for the one thing that decision constrains.
- `payroll_contributions` stays its own table (`PayrollItemContribution`)
  because its shape genuinely differs (employee *and* employer amounts,
  plus a link back to the `ContributionRateTable`/`ContributionRateBracket`
  that produced it) — forcing that into the single-amount lines table
  would be the same mistake in reverse.
- No `payroll_taxes` table at all: there's exactly one tax figure per
  employee per period (not a repeating collection like earnings/
  deductions), so `tax_amount` + a nullable `tax_table_id` live directly
  on `PayrollItem`.

**What actually gets computed, and on what basis.** For every employee
whose `currentEmployment.status` is Active and whose
`currentEmployment.payroll_group_id` matches the period's group:
`basic_salary × (12 / PayFrequency::periodsPerYear())` gives the
period's Basic Pay (`periodsPerYear()`: 12/24/26/52 for monthly/semi-
monthly/biweekly/weekly — the standard annualized-periods proration, the
same factor reused for a `Monthly` `CompensationItem`; a `OneTime` or
`Annual` item pays out in full, exactly once, in whichever period
contains its `effective_date`, since `CompensationItem` has no
recurrence field to hang a real "every year on this date" rule on).
Gross earnings feed contribution and tax bracket lookups (one
`ContributionRateTable` per agency, the most-recently-effective one
active for the period; brackets matched against **basic pay**, i.e. the
already-prorated period figure — not a re-derived monthly figure) and
withholding tax (bracket matched against gross earnings minus employee
contributions, using the company's single active `TaxTable`).

**Deliberately not computed: overtime pay and holiday pay peso amounts**,
despite being their own Phase 11 bullets in blueprint §54. Converting
`OvertimeRequest.requested_hours` (or a holiday worked) into pesos needs
an hourly-rate divisor and OT/holiday multipliers, and nothing in this
app models those as data — they're real, jurisdiction-specific payroll
policy (the Philippine convention alone has several competing "divisor"
methods), not a universal constant. Hard-coding a multiplier here would
be exactly the "don't bake a business-critical rate into code" mistake
§39 exists to prevent for government rates, just for a different table.
Same treatment as Leave's accrual scheduler: document the gap rather than
fake it. Building this for real needs a rate-policy configuration entity
first — a natural candidate for whichever phase next touches Payroll.

**Reprocessing replaces each employee's `PayrollItem` wholesale** (delete
+ regenerate, cascading its lines/contributions) rather than diffing —
safe today because nothing downstream depends on the old numbers and
nothing produces an `is_adjustment` line yet. Once Phase 11d adds manual
adjustments, reprocessing will need to preserve `is_adjustment` lines
instead of blanket-deleting; that's Phase 11d's problem to solve, not
this slice's.

**No object-level payslip-ownership check yet** on `PayrollItemController
::show()` — it's gated by `payroll.view` only, same as every other
Payroll admin screen. That's fine for now because nothing employee-facing
exists to need it: there's no self-service payslip portal until Phase 12
("Digital payslip portal") / Phase 13 ("Employee & Manager Self-Service").
CLAUDE.md's "every payslip access checks object-level ownership" rule
becomes live the moment that portal exists — don't forget it then.

`Employment` gained a `payroll_group_id` (nullable, via a new migration
on the existing table — same precedent as `salary_grade_id` in Phase 10)
rather than a field anywhere else, for the same "one source of truth for
current state" reason CLAUDE.md's Employment section already gives for
department/position/branch: which payroll run an employee belongs to can
change over time (e.g. transferred from a weekly-paid role to a monthly-
paid one), so it belongs on the effective-dated history, not a shortcut
column on `Employee`. Set from the same "Record employment change" modal
as salary grade.

**Payroll Adjustments + Validation — the fourth and final Phase 11
slice.** A manual adjustment is just a `PayrollItemLine` with
`is_adjustment=true` (the columns Phase 11c added specifically for this)
— there's no separate `payroll_adjustments` table. Only allowed while
the item's period is `ForReview` (Phase 11's only reachable post-
processing state; Phase 12's approve/finalize/lock states will need
their own rule once they exist), enforced in
`PayrollItemAdjustmentController`, not just hidden in the UI.

**Adjustments only ever affect `gross_earnings`/`total_deductions`/
`net_pay` — never `total_employee_contributions` or `tax_amount`, on a
reprocess or otherwise.** `PayrollCalculationService::recalculateTotals()`
(called after every add/remove) recomputes those three fields from the
item's current lines and stops there; contributions key off
`basic_salary` and tax keys off the auto-generated gross only, matching
how real government contribution/tax tables are keyed off basic/regular
pay, not ad hoc corrections layered on top afterward. A full Reprocess
is a separate, explicit action for when a reviewer actually wants
Phase 11c's whole calculation to run again — and even that preserves
existing adjustment lines rather than discarding them (captured before
the old `PayrollItem` cascade-deletes, re-attached to the new one; see
`PayrollCalculationService::processEmployee()`).

**Validation is flags, not gates.** `PayrollItem::validationIssues()`
returns plain-English warnings (negative net pay; no tax table matched)
surfaced as a badge on the period's employee list and an alert banner on
the item detail page. Phase 11 has nowhere to attach a hard block —
there's no Approve/Reject yet, that's Phase 12 — so these stay
informational rather than blocking processing or adjustments. Kept
short and concrete on purpose rather than growing into a rules engine;
add to the list only when a real scenario needs flagging, the same
"don't build for hypotheticals" restraint as everywhere else in this
codebase.

**Bug caught by browser verification, fixed before shipping:** the item
detail page originally gave both cards in the right-hand column
(Government Contributions, Deductions) their own `h-100` class, intending
to match the Earnings column's height. With two stacked cards sharing one
flex column, `h-100` instead made each card fight to fill the *stretched
column's* full height independently, pushing the Deductions card's
content out of its visible box (its Remove button became unclickable —
caught by an automated click failing, not just a visual glance). `h-100`
only makes sense for a single card per column; removed it from all three
cards on this page now that the right column stacks two.

Phase 11 (Payroll Engine) is now complete end-to-end: Government Rules
(11a) → Payroll Groups/Periods (11b) → the calculation engine (11c) →
adjustments and validation (11d). Everything through `ForReview` works;
Review/Approve/Finalize/Lock/Publish and payslip generation are Phase
12's job, per blueprint §54's own phase split — don't start building
those without re-reading that section first.

## Payroll Approval (Phase 12, in progress)

Phase 12 is "Payroll Approval & Digital Payslip" — review/approval/
finalization/locking are done (this section); payslip generation, PDF
generation, the digital payslip portal, payslip access logging, and
employee notifications are still ahead. See Status above for exactly
which bullets are done.

**The rest of blueprint §15's state machine is wired up, in the order
blueprint §14's lifecycle diagram gives (Approve → Finalize → Lock →
Generate Payslips → Publish), not the bare list order §15 itself
happens to print (which shows Published before Locked).** The two
sections disagree on where Published sits; the lifecycle diagram is the
more detailed, intentional source, so that's the one this app follows —
worth knowing if you're cross-checking against §15 directly. Concretely:
`ForReview` -[Submit for approval]-> `ForApproval` -[Approve]->
`Approved` -[Finalize]-> `Finalized` -[Lock]-> `Locked` -[Publish]->
`Published`. All six transitions live as plain guarded actions directly
on `PayrollPeriodController` (`abort_unless` on the expected prior
status), the same shape as `LeaveRequestController::approve()`/
`reject()` — CLAUDE.md's "payroll logic never lives in controllers" rule
is about *calculation* math (that's `PayrollCalculationService`'s job),
not simple state-guarded status changes, so there was nothing to extract
into a service here.

**Reject sends `ForApproval` back to `ForReview`, not to `Draft`.**
`ForReview` is already the state where adjustments and Reprocess are
both available (Phase 11c/11d), so a rejected period is immediately
actionable again without losing anything or forcing a from-scratch
redo. Rejecting requires a reason (`rejection_reason`, same required-
string-reason pattern as `LeaveRequestController::reject()`), shown as
a banner on the period's show page once it's back in `ForReview`.

**No dedicated `payroll.review` or `payroll.publish` permission exists
in the seeded catalog** (it only has view/create/process/approve/
finalize/lock/export), so `submitForApproval()` reuses `payroll.process`
(review is still part of the process/review workflow that got the
period into `ForReview` in the first place) and `publish()` reuses
`payroll.lock` (Publish immediately follows Lock in the lifecycle
diagram, and there's no separate permission carved out for it) — the
same "reuse an existing name, document why" move Compensation made for
`organization.manage`.

**Eight new audit columns on `payroll_periods`** (`submitted_for_
approval_at`/`submitted_by`, `approved_at`/`approved_by`, `rejection_
reason`, `finalized_at`/`finalized_by`, `locked_at`/`locked_by`,
`published_at`/`published_by`) via a new migration on the existing
table, same precedent as `processed_at`/`processed_by` in Phase 11c.
No new tables — `PayrollPeriod` already has everywhere the audit trail
needs to live.

**Finalize is where CLAUDE.md's "payroll is immutable once finalized"
rule actually starts biting** — and it required no new guard code to
enforce, because Phase 11c/11d's guards were already written in terms
of "not past `ForReview`": `PayrollCalculationService::process()` only
accepts `Draft`/`ForReview`, and `PayrollItemAdjustmentController` only
allows adjustments while `ForReview`. `Approved`, `Finalized`, `Locked`,
and `Published` were already outside both allowed sets before this
slice existed. Confirmed by
`PayrollLifecycleTest::test_finalized_period_cannot_be_reprocessed()`
rather than assumed.

**Bug caught by browser verification, fixed before shipping:** none this
slice — the lifecycle buttons and reject-reason banner all rendered
correctly on first Playwright pass, likely because they reuse the exact
same form/modal/status-badge patterns already proven out in 11c/11d
rather than introducing new UI mechanics.

## Payslip PDF (Phase 12, continued)

`barryvdh/laravel-dompdf` (pure-PHP rendering, no external binary like
wkhtmltopdf to install) generates a payslip PDF from
`resources/views/payroll/payslip-pdf.blade.php` -- a standalone HTML
document, not `@extends('layouts.admin')`, since it's rendered by
dompdf rather than a browser.

**No `Payslip`/`PayslipItem` tables, despite blueprint's ERD listing
them separately from `payroll_runs`/`payroll_run_employees`.** A
`PayrollItem` (+ its `PayrollItemLine`s and `PayrollItemContribution`s)
already holds everything blueprint §16 lists as payslip content --
generating a payslip is a rendering concern against existing data, not a
second copy of it. Same reasoning as Phase 11c's `payroll_runs`
consolidation.

**The payslip PDF's Deductions section deliberately differs from the
admin item-detail page's Government Contributions card**: the PDF shows
only the *employee* share of each contribution (SSS/PhilHealth/Pag-IBIG
each as one deduction line) plus tax and other deductions, matching
blueprint §16's own "Payslip Deductions" list exactly -- an employer's
matching contribution is a cost to the company, not something deducted
from the employee, so it has no business appearing on their payslip even
though the admin-facing page correctly shows both shares for the
payroll team's own accounting view.

**Eligible for download starting at `Finalized`, through `Locked` and
`Published`** (`PayrollItemController::PAYSLIP_ELIGIBLE_STATUSES`),
gated by `payroll.export`. Blueprint §14's lifecycle diagram places
"Generate Payslips" between Lock and Publish; allowing it from
`Finalized` gives payroll admins a preview window on the now-immutable,
official numbers before the employee-facing Publish step -- this route
is admin-only (`payroll.export`), so there's no risk of an employee
seeing an unpublished payslip through it. The actual employee-facing
"only after Published, only my own" rule is Phase 12c's job once the
portal exists.

**Bug caught by browser verification, fixed before shipping:** the
Total Deductions row used the HTML entity `&minus;` (U+2212, the true
mathematical minus sign) for the leading sign, copying the convention
used throughout the admin Bootstrap views. Those render fine in a real
browser, but dompdf's default core Helvetica font doesn't carry that
glyph and silently substituted "?" in the rendered PDF -- caught by
reading the actual downloaded PDF's extracted text, not just eyeballing
the HTML template. `&ndash;`/`&middot;` elsewhere on the same page
render correctly (they're in the font's encoding); only the true minus
sign isn't. Fixed by using a plain ASCII hyphen (`-`) instead. Worth
remembering for any future PDF template: don't assume an HTML entity
that renders fine in Chrome renders the same way through dompdf.

## Digital Payslip Portal (Phase 12, completed)

Phase 12 is done: the state machine (12a), payslip PDF (12b), and this
slice (12c) -- a minimal employee-facing portal, `users.employee_id`,
payslip access logging, and a publish notification.

**`users.employee_id`** (nullable, unique, new migration) is the piece
blueprint §17's `payslip.employee_id === auth()->user()->employee_id`
rule depends on and that genuinely didn't exist anywhere in the app
before now -- confirmed by grepping every migration before writing this
one. An admin links an account to its `Employee` record from the
existing Users create/edit forms (`UserController::linkableEmployees()`
excludes employees already claimed by a *different* user, so the
dropdown can't create a duplicate link; the DB unique index is the
actual guarantee, the query is just so the form doesn't offer a
guaranteed-to-fail option). Deliberately mass-assignable (unlike
`is_system_account`/`is_protected`) -- linking an account to an employee
isn't a protection escalation, it's routine admin data entry, so it
belongs on `$fillable` like `disabled_at` does.

**A real bug caught before shipping, not by a browser but by the
existing test suite**: `$validated['employee_id'] ?: null` in both
`store()` and `update()` threw `Undefined array key "employee_id"`
against `UserManagementTest`'s existing create-user test, which (like a
real HTML form whose `<select>` always submits *something*, even
`employee_id=""`) doesn't build the exact shape `?:` assumed -- it never
sends the key at all. `nullable` validation doesn't guarantee the key
exists in `$validated`; it only skips *other* rules when absent. Fixed
to `($validated['employee_id'] ?? null) ?: null` -- `??` handles the
key being entirely missing, `?:` handles it being present but an empty
string (what a real "None" `<select>` submits). Worth remembering
anywhere else a nullable `<select>`-backed FK gets this treatment.

**No full employee self-service portal** -- blueprint §41's sidebar
(`layouts/partials/portal-sidebar.blade.php`) is built out in full as
static placeholders, same "real link where it's built, disabled
elsewhere" convention as the admin sidebar, but only **My Payslips**
under Payroll is real. Profile, Employment, Attendance, Leave, and
everything else on that sidebar are blueprint §18's bullets, which are
Phase 13's job ("Employee & Manager Self-Service"), not Phase 12's
("digital payslip portal" specifically) -- don't build ahead of that
phase just because the sidebar shape is already there.

**Portal ownership check has no permission-based bypass**, unlike
blueprint §17's literal wording ("`payslip.employee_id ===
auth()->user()->employee_id` *unless the user has an appropriate payroll
permission*"). `PayslipController::authorizeOwnership()` only checks the
ID match. That's intentional, not a shortcut: a `payroll.export` holder
already has their own separate, fully-featured route to any payslip
(`PayrollItemController::downloadPayslip()`, Phase 12b) -- replicating
the bypass here would just be a second, less-audited path to the same
data. `PayslipPortalTest::test_admin_payroll_permissions_do_not_bypass
_portal_ownership()` pins this down explicitly so it can't regress into
"simpler" by accident.

**`payslip_access_logs`** records `viewed` (on `show()`) and
`downloaded` (on `download()`) -- blueprint §17 also lists "printed" and
"exported", which aren't real actions this app has (printing is a
browser feature no server-side code sees; "exported" would only apply
to the admin's separate CSV/bulk-export tooling, not the single-payslip
routes here), so the enum only has the two it can actually produce.

**`PayslipPublished` notification** fires once per employee when their
period is published (`PayrollPeriodController::publish()`, after the
status update, looping `payrollItems` eager-loaded with `employee.user`
and skipping employees with no linked account) -- same `Queueable`-but-
not-`ShouldQueue` shape as `SecurityAlert`, sent via the same `$user->
notify(...)` call site convention rather than `notifyNow()`.

## Employee Self-Service (Phase 13, completed)

Phase 13 is "Employee & Manager Self-Service" (blueprint §54). First
slice (13a) done: read-only **My Profile** in the portal (bio overview,
Employment History, Documents), reusing `Employee`/`Employment`/
`EmployeeDocument` -- no new tables. `Portal\ProfileController` mirrors
the admin employee-show page's three matching tabs, but as fresh,
simpler read-only partials rather than including the admin ones
directly: the admin Documents tab hard-codes
`admin.employees.documents.download` (gated by `employees.view`), which
an employee viewing their own record won't have, so reusing it verbatim
would have meant either granting that permission (wrong: it grants
access to *every* employee's documents, not just this one's) or forking
the route out of the shared partial anyway. A fresh portal-side
`downloadDocument()` action with its own `document.employee_id ===
auth()->user()->employee_id` check was simpler than trying to make one
partial serve two different authorization models.

**Still no "update permitted information"** (a real §18 bullet) --
viewing is this slice's whole scope; editing self-service fields is a
separate, not-yet-built slice. Don't assume the profile page is
edit-ready because the tabs mirror the admin page's tabs.

**Not built yet, deliberately, because their underlying modules don't
exist**: My Compensation, My Benefits (Benefits is Phase 16), My
Overtime/My Schedule request views, Performance, Training (Phase 15) --
blueprint §18 lists "View benefits/training/performance" as employee
bullets, but there's nothing to view until those modules are built.
Same phase-order discipline as everywhere else in this file: the portal
sidebar shows them as placeholders (matching blueprint §41's full nav
shape), not silently omitted, so it's clear what's planned versus what's
out of scope for now.

**13b — self-service Leave and Overtime request submission.**
`Portal\LeaveController`/`Portal\OvertimeController` are new controllers,
not extensions of `Admin\LeaveRequestController`/
`Admin\OvertimeRequestController` — the admin `store()` actions take an
`employee_id` field and let the caller (an HR user, gated by
`leave.create`/`attendance.manage`) submit on behalf of *any* employee;
retrofitting that to also serve self-service would mean either accepting
a caller-supplied `employee_id` from a portal user (a direct object
reference vulnerability — nothing would stop an employee editing the
hidden field to submit as a coworker) or branching the same method on
who's calling, which is harder to read and reason about than two small,
separately-scoped controllers. The portal versions hard-code
`employee_id = auth()->user()->employee_id`; there is no such input
field on those forms. `approve()`/`reject()` stay exactly where they
were — admin/manager-only, unchanged.

`Portal\LeaveController::cancel()` duplicates
`Admin\LeaveRequestController::cancel()`'s logic (reverse the balance via
`LeaveBalanceService` if the request was `Approved`, otherwise a bare
status change) rather than sharing it, for the same reason: the two
differ only in their ownership check (any employee vs. the caller's own),
and factoring that one line out into a shared method wasn't worth the
indirection for logic this short. `approve()`/`reject()` were *not*
duplicated onto the portal side — an employee never approves their own
leave.

**13c — self-service Attendance Correction Requests.** A genuinely new
employee-initiated workflow (`attendance_correction_requests`), distinct
from `attendance_correction_logs`: the logs table is an audit trail
written *during* a correction (old/new value + reason, once something
has already changed); the new table is a request awaiting a decision
that hasn't changed anything yet. `Portal\AttendanceController::store()`
creates one against an `Attendance` row the caller owns
(`attendance->employee_id === auth()->user()->employee_id`, 404
otherwise) — an employee proposes a `requested_time_in`/
`requested_time_out`/`requested_status` plus a reason; nothing about the
`Attendance` row itself changes yet.

The interesting design decision is on the admin side.
`Admin\AttendanceCorrectionRequestController::approve()` doesn't
re-implement "update the attendance row and log the change" — it calls
the exact same `AttendanceCorrectionService::apply()` that
`Admin\AttendanceController::update()` (a direct HR correction) already
used, so `computeMinutes()` and the audit-logged old/new value write to
`attendance_correction_logs` happen through one path regardless of
whether the correction originated as an employee request or a direct HR
edit. That service was extracted from `AttendanceController` for this
purpose — it's the first file in the previously-empty
`app/Domain/Attendance/`, following the same "extract once a second call
site needs the same logic" rule `LeaveBalanceService` set in Phase 9.
`reject()` only changes the request row's own status/reason — there's
nothing to unwind since the attendance row was never touched. Both
actions require `attendance.correct` (the same permission direct
correction uses, not `attendance.manage`) and guard
`status === Pending` so a decision can't be re-applied or reversed by
resubmitting the form.

**13d — COE (Certificate of Employment) requests.** Blueprint §25:
Request COE → HR Approval → Generate PDF → Available in Portal, with
four supported variants (`App\Enums\CoeRequestType`): Standard, With
Compensation, Without Compensation, Employment Verification.
`Portal\CoeRequestController::store()` lets an employee submit a
`type` + optional free-text `purpose` (e.g. "Bank loan application")
against their own record; nothing is generated yet at this point.

"Generate PDF" isn't a separate step or a stored file — approve()
freezes a snapshot of the employee's *current* `Employment` (position
title, department name, employment status, salary if the type is
`WithCompensation`) plus their earliest `Employment` row's
`effective_date` as "date hired" onto five `snapshot_*` columns on the
`coe_requests` row itself, and the PDF is rendered from that frozen
snapshot on every download — the same "render on demand from data
that's already immutable" shape `Portal\PayslipController::download()`
uses for payslips, just applied to a snapshot instead of a naturally-
immutable finalized-payroll row. Freezing at approval time matters
because current `Employment` is *not* immutable the way a finalized
`PayrollItem` is: without a snapshot, a COE re-downloaded after the
employee's next promotion would silently show different data than the
copy already handed to whatever bank or embassy asked for it — the same
"never silently change a historical record" principle CLAUDE.md applies
to compensation and payroll, extended to a certificate once issued.
No `AttendanceCorrectionService`-style extracted domain service here —
unlike attendance corrections, there's only one call site for the
snapshot logic (`Admin\CoeRequestController::approve()`); CLAUDE.md's
own rule is not to add `app/Domain/` structure until a second caller
actually needs the same logic.

No `coe.*` permission group — approving/downloading is a per-employee-
record action, same shape Compensation reused `employees.view`/
`employees.update` for, so this does too. The one addition: approving or
downloading a `WithCompensation` request also requires
`employees.salary.view`, a permission the catalog has reserved since
Phase 4 but that nothing checked until now (only Payroll Administrator
is seeded with it — not even HR Administrator/HR Staff). This is
blueprint §19's "don't automatically give access to salary" rule,
applied to the one place in this module where compensation could leak
onto a document; the admin index view hides the Approve button (shows
"Requires salary access" instead) on a `WithCompensation` row when the
viewer lacks it, rather than showing a button that would just 403.

**13e — Manager Self-Service, via data scope rather than a new portal.**
Blueprint §19 reads like it wants a dedicated manager UI, but this
codebase's RBAC design already put Manager-role users in `/admin`
alongside every other role (`employees.view`, `leave.approve`,
`attendance.view`, scoped Team) — the blueprint's own §41 Employee
Portal Sidebar has no "My Team" section either, so there's no dedicated
manager UI to build. The actual gap was that `roles.data_scope` had
never been queried anywhere (documented above under "Data scope" since
Phase 4) — a Manager could already reach `Admin\LeaveRequestController
::approve()` etc. through the exact permissions they're seeded with, but
nothing stopped them acting on *any* employee's request, not just their
own team's. Phase 13e is `DataScopeResolver` plus wiring it into
`EmployeeController`/`LeaveRequestController`/`AttendanceController`/
`OvertimeRequestController` (see "Data scope" above for the full
design) — "View team" / "View team attendance" / "Approve leave" /
"Approve overtime" are now real, correctly-scoped capabilities of the
existing Manager role, no new routes or views needed. "Conduct
performance reviews" and "View team statistics" stay unbuilt (Performance
is a later, not-yet-built module; no statistics/reporting view exists
for this yet).

**13f — Requests aggregation view, closing out Phase 13.** Blueprint
§41's portal sidebar lists "Requests" as one flat item alongside the
type-specific pages, not a replacement for them — `Portal\RequestController
::index()` reads the employee's `leaveRequests`/`overtimeRequests`/
`attendanceCorrectionRequests`/`coeRequests`, normalizes each into a
common `{type, date, detail, status, link}` shape, and merges/sorts them
by date. Purely a read-only presentation merge with no shared invariant
to protect (unlike `LeaveBalanceService`), so it lives directly in the
controller rather than a Domain service — each row's `link` just points
back to that type's own page for the actual submit/cancel/download
actions, which stay exactly where Phase 13a-13d built them.

Every blueprint §18/§19 bullet buildable without a not-yet-built module
(Benefits, Performance, Training) now has a real implementation. Phase
13 is complete for what's buildable today; "View benefits/training/
performance", "Conduct performance reviews", and "View team statistics"
remain documented gaps waiting on their own later phases, not oversights.

## Recruitment (Phase 14, complete)

Blueprint §8's applicant lifecycle (Application → Screening → Interview
→ Assessment → Final Interview → Job Offer → Hired → Onboarding) starts
from a posting; blueprint §54 lists Phase 14 as one phase, but it's the
biggest single module left, so it's being built as sub-slices the same
way Payroll (11a-11d) and Employee Self-Service (13a-13f) were.

**14a — Job Requisitions + Job Postings.** A requisition
(`job_requisitions`) is the headcount *request* — `department_id`/
`position_id` are both nullable since a requisition can predate a formal
position row ("we need 2 more support engineers"). It follows the exact
same request-then-decide shape as Leave/Overtime/Attendance Correction/
COE: `store()` always creates `Pending`, and `approve()`/`reject()` are
the only way status moves — no `edit()`, matching how none of this
app's other request types let you revise a submitted request either
(resubmit a new one instead). Both actions are gated by `recruitment
.manage`, the only manage-shaped permission the seeded catalog has for
this module (no separate approval permission exists, unlike Leave's
`leave.approve`/`leave.reject` split) — reusing what's seeded rather
than inventing a new one, same rule Compensation (Phase 10) established.

A posting (`job_postings`) can only be created against an **approved**
requisition (`Rule::exists('job_requisitions','id')->where('status',
'approved')` in `JobPostingController::store()`, not a DB constraint —
same "app-level rule on top of the FK" approach Organization's hierarchy
validation uses). `company_id` is denormalized onto the posting from its
requisition, the same pattern every Organization entity uses, so postings
can be queried/scoped without joining through `job_requisitions`. A
posting's own lifecycle is separate from the requisition's:
`draft → published → closed`, via `publish()`/`close()` actions
(`published_at` stamped on publish). Editing (`title`/`description`/
`is_internal`/`closes_at`) is unrestricted by status — blueprint doesn't
call for locking a published posting's copy, and there's no history
requirement here the way there is for compensation/employment data.

**Consolidated away from blueprint's suggested ERD, same restraint as
every prior phase:** no `recruitment_statuses` table — the lifecycle's
stages are a fixed, developer-known vocabulary
(`App\Enums\JobRequisitionStatus`, `App\Enums\JobPostingStatus`), the
same reasoning `AttendanceStatus`/`LeaveRequestStatus`/every other
status field in this app already uses a PHP enum instead of an
admin-editable lookup table. No `applicant_sources` table either, for
the same reason, once Applicants land in 14b. No `interviewers` join
table planned for 14c — v1 is one primary interviewer per interview
round (schedule multiple rounds for a panel instead), which also means
no separate `interview_evaluations` table: with a single interviewer per
row, the evaluation (score/recommendation/notes) can just be columns on
`interviews` itself rather than a 1:1 wrapper table, the same collapsing
`SalaryGrade`/`PayrollItem` already did for their own suggested ERDs.

**14b — Applicants + Applications.** An `Applicant` is deliberately
**not** company-scoped, unlike every other entity in this app — see the
migration comment for why (a candidate-pool profile can apply to
postings across different companies; company-scoping happens per
`Application`, via `job_posting → job_requisition → company_id`, the
same denormalization chain 14a already set up). The resume is a single
file directly on the `Applicant` row (`resume_path`/
`resume_original_filename`, private `local` disk, authenticated
download action checking `recruitment.view`) — the same
"single-file-on-the-record" shape as `Employee::profile_photo_path`,
not a full `ApplicantDocument` sub-resource, since blueprint's own
function list already separates "Resume" from `applicant_documents` and
only the former is in scope here.

An `Application` links one applicant to one posting (`unique(
[applicant_id, job_posting_id])` — the same applicant can't apply to the
same posting twice, though they can apply to different postings, which
is the whole point of keeping Applicant and Application separate
tables). `ApplicationController::store()` only accepts a **published**
posting (`Rule::exists(...)->where('status','published')`), reached from
a modal on the applicant's own profile page (the natural entry point is
"apply this candidate," not a blank two-picker form) — the same
per-record-modal convention Employee's sub-resources (Phase 6) use.

`updateStatus()` moves an application through `App\Enums\ApplicationStatus`
(`Applied → Screening → Interview → Assessment → FinalInterview →
Offered → Hired`, plus the terminal `Rejected`/`Withdrawn`) without
enforcing strict linear ordering — a plain dropdown lets HR pick any
non-terminal status directly, `Rejected` alone requires a reason (its
own modal, matching every other reject flow in this app). This is a
deliberate simplification: blueprint's Workflow Engine (§27) is an
explicitly not-yet-built module, and this app already avoids bespoke
approval chains elsewhere (Overtime, Phase 8) rather than half-building
one ahead of it — once an application reaches a terminal status
(`ApplicationStatus::isTerminal()`), it can't be changed again.

**14c — Interviews + Assessments.** Both are nested under `Application`
(not `Applicant` — the same interview round is specific to one posting's
pipeline, not the candidate globally). `Interview` is one row per
scheduled round: `interviewer_id` (nullable FK to `Employee`),
`type` (`App\Enums\InterviewType`: phone screen/technical/behavioral/
panel/final), `scheduled_at`, `location`, and — since v1 has exactly one
interviewer per row (see 14a's note on why there's no `interviewers`
join table) — the outcome columns (`rating` 1-5, `recommendation`,
`feedback`) live directly on the same row rather than a separate
evaluation table. `InterviewController::update()` handles both
rescheduling and recording the outcome through one form/modal, since a
single row only ever needs one edit surface.

`Assessment` is simpler: `type` (`App\Enums\AssessmentType`), `due_at`,
then `completed_at`/`score`/`passed`/`notes` filled in once a result
comes back. `assessed_by` is stamped automatically from whoever submits
the update *that sets `completed_at`* — there's no separate "assign a
grader" step in v1, matching this module's general preference for one
combined edit action over multiple narrow ones.

Both live on a new `Admin\ApplicationController::show()` page (Interviews
section, Assessments section, plus the status-update controls moved
here from the applicant's own page now that an application has enough
of its own related data to deserve a dedicated page) — the applicant
profile page and the flat applications index both now link to it
instead of hosting the status controls inline.

**14d — Job Offers + hiring conversion.** `JobOffer` is nested under
`Application` (not a new pipeline entity of its own) — `store()` extends
a Pending offer and moves the Application to `Offered`; `accept()`/
`decline()` record the candidate's decision (there's no candidate-facing
portal, so HR records it on their behalf); `rescind()` lets HR withdraw
an unanswered offer. At most one Pending offer per application at a
time, enforced in `Application::hasPendingJobOffer()` rather than a DB
constraint — the same "app-level rule on top of the FK" approach
`PayrollPeriod`'s overlap check uses — so a declined or rescinded offer
can always be followed by a fresh one on the same application.

Because a real `JobOffer` entity now exists, `ApplicationController::
updateStatus()` no longer accepts `offered`/`hired` as manually-chosen
values (`Rule::notIn`, with a custom message) and the status dropdown on
the show page hides both — before this slice that endpoint would happily
mark an application `Hired` with no offer or `Employee` behind it at
all. `Offered`/`Hired` are now reachable only through the offer
lifecycle and `convert()` below.

`JobOfferController::convert()` is the hiring-conversion step CLAUDE.md
previously listed as "not built yet": it turns an Accepted offer into a
real `Employee` + `Employment` row (`change_type=Hire`,
`effective_date` = the offer's `start_date`, `basic_salary` = the
offer's `offered_salary`), the actual integration point back from
Recruitment into Phase 6/7's tables. First/last name, email, and mobile
come from the `Applicant` record, not re-entered — the convert form only
collects `employee_number` (required, genuinely new) plus the handful of
optional bio fields `Applicant` never captured (middle name, suffix,
preferred name, birth date, gender, civil status, nationality), rather
than reusing `EmployeeController`'s form wholesale. `converted_
employee_id`/`converted_at` freeze the outcome on the `job_offers` row
itself (same shape as `CoeRequest`'s snapshot columns) and double as the
guard against converting the same offer twice.

**`convert()` requires `employees.create` in addition to
`recruitment.manage`** — creating the actual Employee master record is
exactly what that permission gates everywhere else in the app, and
crossing from Recruitment into Core HR data deserves both, not a reused
single permission. No seeded role currently holds both (Recruitment
Officer lacks `employees.create`; HR Administrator/HR Staff lack
`recruitment.manage`), so completing a hire today needs either
Superadmin or granting one role both permissions through the Roles UI —
the same "permission exists, nothing's granted it by default yet"
pattern CLAUDE.md already documents for `organization.manage`, not a bug
to fix here.

**14e — Onboarding, closing out Phase 14.** Blueprint §9: a reusable
checklist definition (`OnboardingTemplate` + `OnboardingTask`, company-
scoped CRUD, same shape as `LeaveType`/`Holiday`/`PayrollGroup`) assigned
per-hire (`EmployeeOnboarding` + `EmployeeOnboardingTask`). Assigning a
template *copies* its tasks onto the employee's own rows rather than
referencing the template's live rows — editing or adding to a template
afterward never changes an already-assigned employee's checklist out
from under them, the same snapshot principle Employee Self-Service's COE
requests use for their own frozen `Employment` data. Blueprint's ERD
names the third table `employee_onboarding` (singular); this app uses
Eloquent's default pluralization (`employee_onboardings`) instead, same
as everywhere else in the codebase — the blueprint's table names are a
starting sketch, not a literal contract.

**No status column on `EmployeeOnboarding`** — completion is computed
(`isComplete()`/`progressPercentage()`) from whether every child task
`is_completed`, rather than a second, independently-settable field that
could drift out of sync with the tasks themselves. This is the same
"compute rather than duplicate" call `Application::hasPendingJobOffer()`
makes for its own state check. An `EmployeeOnboarding` with zero tasks
counts as complete (nothing left to block on) rather than stuck forever.
`is_completed` on a task is a plain boolean, not an enum — genuinely
binary (done or not), unlike `Assessment`'s pending/passed/failed which
needed a third state.

**Two different permission groups gate the two halves of this slice, by
shape, the same split Compensation (Phase 10) established.**
`OnboardingTemplate`/`OnboardingTask` are company-wide configuration
data (the same shape as `LeaveType`/`Holiday`), so they reuse
`recruitment.view`/`recruitment.manage` — unlike Compensation, Onboarding
*is* blueprint's own Phase 14 module alongside Recruitment, so this
isn't even a cross-module borrow. `EmployeeOnboarding`/
`EmployeeOnboardingTask` (assigning a checklist to a specific hire,
checking off its tasks) are a per-employee record (the same shape as
`Employment`/`CompensationItem`), so they reuse `employees.view`/
`employees.update` instead, and live in `Admin\EmployeeOnboardingController`
under the employee routes rather than the recruitment ones — onboarding
happens post-hire, once a real `Employee` row already exists, not as
part of the pipeline itself. At most one *incomplete* checklist per
employee at a time (checked with the same `whereHas` pattern
`hasPendingJobOffer()` uses, not a DB constraint); a completed one never
blocks assigning a fresh one.

The checklist itself lives as a new **Onboarding** tab on the employee
profile page (`admin.employees.show`), reusing the exact tab-per-entity
shape Phase 6 established — each assigned checklist is its own card with
a progress bar, and each task row's checkbox auto-submits on change
(`onchange="this.form.submit()"`, the same pattern the filter dropdowns
already use elsewhere) rather than a batch "Save" button, since a
checklist this size doesn't need one. The sidebar's long-placeholder
TALENT > Onboarding entry (`'Onboarding' => null`, already scaffolded
per blueprint §41's full nav shape) now points at
`admin.recruitment.onboarding-templates.index`, the same "real link
where it's built" convention the rest of this file already follows.

Blueprint §54's Phase 14 (Job requisitions, Job postings, Applicants,
Applications, Interviews, Assessments, Job offers, Onboarding templates,
Onboarding tasks) is now complete end-to-end. Offboarding (blueprint
§26) is a separate, later module — Phase 16's job, not this one's — see
Status above before starting Phase 15.

## Talent Management (Phase 15, complete)

Blueprint §54 lists Phase 15 as Performance, Goals, KPIs, Competencies,
Skills, Training, Career development, Succession planning — built as
sub-slices like every other multi-entity phase.

**15a — Performance Cycles + Goals.** `PerformanceCycle` (company-scoped
CRUD, same shape as `LeaveType`/`Holiday`/`PayrollGroup`) has its own
lifecycle, `Draft → Active → Closed`, via `activate()`/`close()` actions
rather than a freely-editable status field — the same guard shape
`JobPosting`'s `publish()`/`close()` established, rather than
reinventing `PayrollPeriod`'s per-module transition pattern differently
each time. Unlike Compensation (Phase 10), Performance already has its
own seeded permission group (`performance.view`/`performance.manage`),
so both cycles and goals use those directly — no borrowed-permission
workaround needed here.

**No separate KPIs table**, despite blueprint §22 listing "Goals" and
"KPIs" as separate functions — `performance_goals` carries nullable
`target_value`/`actual_value`/`unit` columns so a goal *can* be
KPI-like (a measurable target) without forcing every goal to have one,
the same "one flexible table, not two near-identical ones" call
`CompensationItem` made for allowances/bonuses/incentives. A goal
belongs to exactly one `PerformanceCycle` (required, not nullable) —
grouping goals by cycle gives every goal a clear time boundary, the
same reasoning `CompensationItem` needs an effective/end date.

`PerformanceGoal` is managed from a new **Performance** tab on the
employee profile page (`admin.employees.show`), the same nested-
controller-plus-modal shape Phase 6 established for per-employee sub-
resources (`EmployeePerformanceGoalController`, routes under
`admin.employees.performance-goals.*`) — status
(`NotStarted`/`InProgress`/`Completed`/`Cancelled`, a plain enum since
it's genuinely discrete, unlike `Assessment`'s pending/passed/failed
which needed a third state for a different reason) is edited alongside
the goal's own fields in the same edit modal, not a separate action.

**15b — Performance Reviews.** Self-review, manager review, and peer
review (blueprint §22) are one table/controller differentiated by a
`type` enum (`App\Enums\PerformanceReviewType`), not three near-identical
ones — the same "one flexible table" call `CompensationItem` and 15a's
Goals/KPIs collapse already made. "Ratings" and "Comments" are likewise
just `rating` (1-5, matching `Interview`'s existing rating scale) and
`comments` columns on the same row, not their own tables. "Performance
history" isn't a table either — it's every review/goal row for an
employee across cycles, queried on the existing profile tab, not
duplicated anywhere. `PerformanceReview` is managed from the same
Performance tab as Goals (`EmployeePerformanceReviewController`, routes
under `admin.employees.performance-reviews.*`), gated by
`performance.manage` like Goals.

Status moves only `Draft` → `Submitted` → `Acknowledged` via
`submit()`/`acknowledge()` guarded actions, the same lifecycle shape
`PerformanceCycle`'s `activate()`/`close()` established — `update()`/
`destroy()` are only allowed while `Draft`, so a review already shown to
(or acknowledged by) the employee can't silently change after the fact,
the same "don't overwrite an issued record" principle the COE snapshot
uses, applied here without needing an actual snapshot since the review
row itself is what's being protected. `submit()` additionally requires
`rating` to already be set — an unrated review can't be submitted.

`reviewer_id` always points at an `Employee` row (for a self-review it
equals `employee_id`); a self-review must name the employee as their own
reviewer and a manager/peer review must not, both checked in the
controller rather than a validation rule, the same "needs the route's
`$employee` for comparison" reasoning `Employment.manager_id`'s
self-check already uses. **At most one Self and one Manager review per
employee per cycle; Peer reviews are unrestricted** (several peers can
weigh in) — an app-level closure validation rule, not a DB unique index
since it only applies to two of the three types, the same shape
`PayrollPeriod`'s overlap check and `Application::hasPendingJobOffer()`
already use for constraints a DB constraint can't express.

Acknowledgement is admin-side only in this slice — there's no portal
self-service action for an employee to acknowledge their own review yet
(unlike blueprint §18's self-service bullets Phase 13 already built for
Leave/Overtime/Attendance/COE). Extending Employee Self-Service to cover
this is a candidate for a later slice, not silently out of scope.

**Bug caught by browser verification, fixed before shipping:** none in
the application — the one issue Playwright surfaced was in the
verification script itself (re-clicking a Bootstrap tab immediately
after `page.goto()`, before its JS had attached, left the tab visually on
Overview for one screenshot) rather than the app; every functional
check (validation rejecting a duplicate manager review, lifecycle
guards blocking edit/delete once submitted, status badges updating
correctly) passed against the real rendered HTML on the first run once
the seed data carried the `employees.view` permission the show page
itself needs.

**15c — Performance Improvement Plans, closing out blueprint §22.** A PIP
is forward-looking (reason, improvement goals, a bounded start/end
period, a closing outcome) rather than backward-looking like a review,
so it's a genuinely separate table (`performance_improvement_plans`),
not a `PerformanceReview` subtype — the collapsing judgment call this
codebase makes everywhere else only applies when two things are the
*same shape*; a PIP and a review aren't. `performance_review_id` is
nullable: a PIP commonly follows a poor review but doesn't have to (it
can also follow a standalone conduct/performance incident), and when set
it's validated against `performance_reviews.employee_id` matching the
route's `$employee` — tighter than the usual company-scope check, since
a review is inherently employee-specific, not just company-specific.
Managed from the same Performance tab as Goals/Reviews
(`EmployeePerformanceImprovementPlanController`, routes under
`admin.employees.performance-improvement-plans.*`), gated by
`performance.manage`.

Lifecycle is `Active` until `close()` moves it to one of three terminal
outcomes — `Successful`/`Unsuccessful`/`Cancelled` (`Rule::in`, not a
bare enum rule, since `Active` itself is never a valid *closing* value)
— stamping `closed_at`/`closed_by`. `update()`/`destroy()` are only
allowed while `Active`, the same "an issued record doesn't silently
change" guard `PerformanceReview`'s Draft-only edit uses, applied here
to the whole record rather than just pre-submission — once closed, a
PIP is a permanent part of the employee's history, favorable or not,
exactly the kind of record CLAUDE.md's general non-overwrite principle
protects even outside payroll/employment.

**Bug caught by browser verification, fixed before shipping:** none in
the application — ground-truthed against the database directly (both
plans existed with the right `performance_review_id`/`status` values)
after a verification-script check came back a false negative from the
same class of timing issue as 15b's (checking page content immediately
after a redirect, before navigating back to the Performance tab).

**15d — Competencies + Skills.** Blueprint's own table of contents
groups these under one heading ("Skills & Competencies"), but they're
kept as two separate tables (`competencies`, `skills`), not collapsed
into one with a `type` enum the way `PerformanceReview`'s self/manager/
peer or `CompensationItem`'s allowance/bonus/incentive are -- those
collapses all share one downstream consumer that treats the variants
interchangeably; a skill and a competency don't (blueprint §23 ties
skills to training/certificates/expiration reminders, a role
competencies don't play), so collapsing them would just produce a table
whose rows mean two different things and that most queries would have
to filter on anyway. Both are plain company-scoped lookups, same shape
as `LeaveType`/`Holiday`, unique on `(company_id, name)` rather than a
code (nothing cross-references either by a compact code the way
Organization's hierarchy entities do). Gated by `training.view`/
`training.manage` -- Training already has its own seeded permission
group (unlike Compensation), and blueprint §23 is the more natural
long-term owner of this catalog even though it's shipping ahead of the
Training module itself, the same "borrow the permission whose shape
fits" reasoning applied one module early.

Per-employee ratings (`EmployeeCompetency`/`EmployeeSkill`) share one
`App\Enums\ProficiencyLevel` (Beginner/Intermediate/Advanced/Expert) --
unlike the parent catalog tables, the *rating* concept genuinely is the
same shape for both, so the enum is reused rather than duplicated. One
row per employee per competency/skill, corrected in place on
reassessment rather than append-only (`Attendance`'s "correct in place"
precedent, not `Employment`'s -- nothing requires preserving every past
rating the way compensation/employment history must). The unique
`(employee_id, competency_id)` / `(employee_id, skill_id)` index is
mirrored in validation via `Rule::unique(...)->ignore(...)`, turning a
duplicate rating into a friendly form error instead of the raw
`QueryException` CLAUDE.md's Attendance section already documents
catching once for Holiday's date uniqueness. Managed from a new
**Skills & Competencies** tab on the employee profile page, lighting up
the TALENT > Skills sidebar placeholder.

**Two real bugs caught by browser verification, both fixed before
shipping, neither the one first suspected.** The Add Competency and Add
Skill modals share one fields partial (`_capability-rating-fields.blade
.php`); Playwright's first full run showed a skill rating saved with the
*previous* competency rating's `assessed_at`/`assessed_by` despite the
skill form never touching those fields. The first fix attempted --
folding `$kind` into the partial's `id="..."` attributes, since both
"new" forms shared ids like `assessed_at_new` -- was a real, worth-fixing
issue (duplicate ids are invalid HTML and break `<label for>`) but
didn't change the actual symptom on retest, proving it wasn't the cause.
The real cause: `_skills-competencies.blade.php` used the same loop
variable name (`$rating`) for both the competency and skill `@foreach`
blocks, and Blade's `@include` shares the *calling* template's variable
scope by default -- the Add Skill modal's `@include(...)` never passed
its own `rating` key, so it silently inherited whatever `$rating` still
held from the competency edit-modals' `@foreach` earlier in the same
file (PHP doesn't scope `foreach` variables to the loop). Fixed by
renaming the loop variables to `$competencyRating`/`$skillRating` and
having every "Add" modal's `@include` pass `'rating' => null` explicitly
rather than relying on the partial's `$rating ?? null` default -- both
changes independently close this class of bug, kept together for
defense in depth. Confirmed by re-running the exact same Playwright
script against a cleared database and checking the saved row directly
rather than trusting the rendered page.

**15e — Training Providers, Courses, and Sessions.** Blueprint §23 lists
"Training catalog," "Training providers," and "Courses" as three
functions; the catalog *is* its courses (no separate wrapper table, the
same call Compensation made for salary bands), so this slice is two
real lookup tables (`training_providers`, `training_courses`) plus a
third for scheduled instances (`training_sessions`). `TrainingCourse`
carries a nullable `training_provider_id` (validated same-company via
`Rule::exists(...)->where('company_id', ...)`) rather than requiring
one — a company can run its own internal training with no external
provider. `TrainingSession.company_id` is denormalized from its course,
the same "every level carries its own direct company_id" rule
Organization's hierarchy already established, letting sessions be
listed/scoped without joining through `training_courses`. "Cost"
(blueprint's own bullet) lives on the session, not the course — a
course's real-world cost varies by provider/session/date and is only
known once a concrete session is scheduled.

Sessions are managed entirely from `TrainingCourseController::show()`
via add/edit modals, no index/create/edit views of their own — the same
shape `ContributionRateTable` already established for its brackets.
Lifecycle is `Scheduled` until `complete()`/`cancel()` moves it to one
of two terminal states (both guarded, no path back); `update()`/
`destroy()` are only allowed while `Scheduled`, the same "don't rewrite
a settled record" guard `PerformanceReview`/`PerformanceImprovementPlan`
already use. Gated by `training.view`/`training.manage` throughout,
extending the training-subnav (`Courses`/`Providers`/`Competencies`/
`Skills`) and lighting up the TALENT > Training sidebar placeholder
alongside the Skills one 15d already lit.

**Bug caught by browser verification, fixed before shipping:** the new
course `show.blade.php` never rendered a `session('status')`/validation-
error alert block, unlike every other custom (non-`<x-admin.resource-
index>`) show page in the app (`admin.employees.show`,
`payroll-periods.show`, `contribution-rate-tables.show`, ...) — Add
Session appeared to silently do nothing in the browser, though the
session row itself was in fact being created correctly each time
(confirmed by re-running the exact same form submission twice and
seeing two identical rows appear). `<x-admin.resource-index>` renders
this block internally for plain list pages, but a custom show page has
to include it itself; this one didn't. Fixed by copying the
`@session('status')` / `$errors->any()` block `admin.employees.show`
already uses verbatim — worth checking for on any future custom show
page that isn't built on `<x-admin.resource-index>`.

**15f — Training Enrollment, Attendance, and Certificates, closing out
blueprint §23.** Three more listed functions, one table
(`training_enrollments`). "Attendance" is folded into a `status` enum
(`Enrolled → Completed/Cancelled/NoShow` — `Completed` *is* "attended"),
the same way `Interview`'s outcome columns live directly on the
interview row rather than a separate evaluation table. "Certificates"
is three nullable columns (`certificate_number`/`_issued_at`/
`_expires_at`) on the same row, not a wrapper table — one enrollment has
at most one certificate, not a repeating collection. `update()` records
the outcome and the certificate together in one action, the same
one-combined-edit preference `Assessment`'s `completed_at`/`score`/
`passed`/`notes` update already established, guarded to fire only once
from `Enrolled` (the same "a decision can't be re-applied by
resubmitting" rule `AttendanceCorrectionRequest`/`CoeRequest` use) —
certificate fields are silently discarded server-side unless the
decision is `Completed`, so a `NoShow` can't accidentally carry one.

**Capacity is enforced, not just displayed** — `TrainingSession
::occupiedSeats()` counts `Enrolled` + `Completed` rows (a `Cancelled`/
`NoShow` frees the seat it held) and `store()` rejects a new enrollment
once that count reaches `capacity`. The field existed since 15e
specifically for this; leaving it unenforced would have made it
decorative. Enrollment also re-validates `Rule::unique('training_enrollments')`
against `(employee_id, training_session_id)`, turning what the table's
own unique index would otherwise reject as a raw `QueryException` into
a friendly form error.

**`TrainingSessionController::show()` is new this slice** — once a
session has a roster to manage, it earns its own page rather than
staying purely modal-driven off the course's show page, the same
"enough of its own related data to deserve a page" call 14c made moving
Interview/Assessment onto `Admin\ApplicationController::show()`. The
course page's session table gained a plain "Roster" link per row;
nothing about 15e's existing Edit/Complete/Cancel actions changed. A
new read-only **Training** tab on the employee profile page
cross-references the same enrollments from the employee's side — no
edit actions there, since decisions are made from the session's roster,
not duplicated onto a second surface.

**Bug caught by browser verification: none this slice.** Every check
(capacity blocking, the friendly duplicate-enrollment error, certificate
fields surviving a Completed decision and being dropped on any other,
locked action buttons once decided, the employee-side Training tab
reflecting the same data) passed on the first Playwright run — likely
because this slice's two new pages (`sessions/show.blade.php`, the
employee `_training.blade.php` tab) were both written with the
`@session('status')`/`$errors->any()` alert block already in place from
the start, after 15e's session-show-page omission of exactly that block
was caught and fixed.

**Expiration reminders is a documented, deliberate gap** — the
same treatment Leave's accrual scheduler and Payroll's OT/holiday pay
already get. `certificate_expires_at` exists and is populated; nothing
runs on a schedule to check it and notify anyone. Don't fake a reminder
in the UI; a real implementation needs a scheduled job, which is a
natural candidate whenever this app grows a job-scheduling story for
the first time (there isn't one yet — no other module has needed
`php artisan schedule:run` before now either).

Blueprint §23 (Training and Learning: catalog, providers, courses,
sessions, enrollment, attendance, cost, certificates, skills,
competencies, expiration reminders) is now complete end-to-end across
15d-15f.

**15g — Career Development + Succession Planning, closing out Phase
15.** The last two functions in blueprint §54's Phase 15 list have zero
detail anywhere in the document — no functions list the way §22/§23
have, and the table of contents entries that would name their sections
(37 "Skills & Competencies", 38 "Career Development", 39 "Succession
Planning") point at section numbers the body actually gives to Payroll
Snapshot and Government Rules; those sections were apparently never
written. Confirmed by grepping for both headings before writing this
slice, the same check 12c's `users.employee_id` section already
documents doing for its own migration.

Given no spec to follow, both are modeled as new per-employee tables on
a **Career & Succession** employee-profile tab, matching every other
Phase 15 entity's shape rather than inventing a new surface:
- `CareerDevelopmentPlan` (`target_position_id` nullable, `target_date`,
  `development_actions`, `Active → Achieved/Cancelled`) reuses PIP's
  exact lifecycle-guard shape — a career plan is the same kind of
  forward-looking record with a bounded outcome a PIP is, just aimed at
  growth instead of correction.
- `SuccessionCandidate` (`position_id`, `readiness` enum, `notes`,
  unique per employee+position) has no lifecycle at all — a candidacy is
  edited in place or removed, not moved through a terminal state.
  Real-world succession planning is usually organized by *position*
  ("who could replace the VP of Engineering"), but this table is
  entered from the *employee* side to avoid adding a `show()` page to
  `Position` (Phase 5, already shipped, not designed to need one) —
  same data, just queried from the other direction if a position-centric
  view is ever needed.

Both reuse `performance.view`/`performance.manage` — Talent
Management's existing group, since neither function has one of its own
in the seeded catalog and nothing about either suggests a dedicated
group is warranted.

**Bug caught by browser verification, fixed before shipping:** none in
application logic — `SuccessionReadiness`'s generic `ucfirst(str_replace
('_', ' ', $value))` rendering rendered the two year-range cases as
"Ready 1 2 years" instead of anything readable. Not a functional defect
(the stored value and every validation/uniqueness rule around it were
correct), but real user-facing text a real HR admin would see, so it
was worth a proper fix: a `label()` method on the enum with an explicit
`match`, the same "enum owns its own display string" idiom worth
reaching for whenever a case's raw value doesn't read cleanly through a
generic transform.

Blueprint §54's Phase 15 (Talent Management: Performance, Goals, KPIs,
Competencies, Skills, Training, Career development, Succession
planning) is now complete end-to-end across 15a-15g.

## Benefits (Phase 16a)

Phase 16 is "Benefits & Offboarding" (blueprint §54); this slice is
Benefits (§21), built the same sub-slice-per-entity-group way every
other multi-entity phase was. Offboarding (§26) is next.

**Only 4 of blueprint's 8 listed "Support" types are genuinely new
here.** §21 lists SSS, PhilHealth, Pag-IBIG, HMO, Insurance, Allowances,
Loans, and Retirement benefits — but SSS/PhilHealth/Pag-IBIG are
government contributions already fully modeled by `ContributionRateTable`
+ `PayrollItemContribution` (Phase 11a/11c), computed every payroll
period, and Allowances already exist as a `CompensationItem` type
(Phase 10). Building a second, disconnected "SSS BenefitPlan" or
"Allowance BenefitPlan" here wouldn't track anything payroll doesn't
already track correctly — it would just create two answers to "what's
this employee's SSS contribution." `App\Enums\BenefitType` therefore
has exactly four cases: `Hmo`/`Insurance`/`Loan`/`Retirement`, the ones
with no existing home in this app.

**`BenefitPlan`** (company-scoped catalog, same shape as `LeaveType`/
`Competency`) carries `name`/`type`/`description`/`eligibility_criteria`
(free text, not a rules engine — blueprint gives no structure for
eligibility beyond the word itself) — deliberately *no* effective/end
date on the plan itself. Blueprint's own "Track" field list (Plan,
Eligibility, Enrollment, Employee contribution, Employer contribution,
Dependents, Effective date, End date) reads as one flat list, but
Effective date/End date describe *coverage*, not the plan definition —
matching how every other effective-dated concept in this app
(`Employment`, `EmployeeSchedule`, `CompensationItem`) puts its dates on
the per-employee row, not the catalog/definition row.

**`BenefitEnrollment` is effective-dated and append-only, the same
shape as `Employment`** — `store()` closes the prior current row
(`end_date IS NULL`) before inserting a new one, so a contribution
change becomes a new row instead of overwriting history. The one
difference from `Employment`: the "close the prior current row" check
is scoped to `(employee_id, benefit_plan_id)`, not just `employee_id` —
unlike current employment, an employee can hold several concurrent
enrollments at once (HMO and a Loan simultaneously), so there's no
single "current" row across *all* of an employee's benefits, only
within one plan.
`BenefitEnrollmentTest::test_concurrent_enrollments_in_different_plans_are_unaffected()`
pins this down explicitly.

**Dependents reuse the existing `EmployeeDependent` table (Phase 6)**
via a `benefit_enrollment_dependents` pivot, rather than a second
benefits-specific dependent concept — "which of this employee's
already-recorded dependents are covered under this enrollment" is the
same person, just flagged covered-or-not per enrollment.
`covered_dependent_ids` is validated with
`Rule::exists('employee_dependents','id')->where('employee_id',
$employee->id)` so one employee's enrollment form can't attach another
employee's dependent.

Gated by `benefits.view`/`benefits.manage` — Benefits' own seeded
permission group, no borrowing needed. Lights up the PAYROLL > Benefits
sidebar placeholder (the plan catalog); enrollment is managed from a new
**Benefits** tab on the employee profile page, no separate `update()`/
`destroy()` — same "no edit, only append" restriction `EmploymentController`
already has, for the same reason.

**Bug caught by browser verification: none this slice** — every check
(catalog CRUD, enrolling, the prior row closing exactly one day before
the new one's effective date, a second concurrent plan being unaffected,
covered-dependent validation and display) passed on the first Playwright
run.

## Offboarding (Phase 16b, closes Phase 16)

Blueprint §26 is a literal ASCII flowchart, not a table/field list like
most other sections: `Resignation → Approval → Notice Period →
Clearance → Asset Return → Final Payroll → Final Pay → COE → Account
Disable → Separated`, with an explicit standalone note, "Never delete
the employee record." One table, `OffboardingRequest`, one row per
resignation.

**`App\Enums\OffboardingStatus` drives the whole pipeline through
`sequence()`/`next()` and a single generic `advance()` controller
action, not ten separate guarded methods.** This is a deliberate
departure from `PayrollPeriod`'s Phase 12a precedent (one dedicated
guarded action per transition — `submitForApproval()`, `approve()`,
`finalize()`, `lock()`, `publish()`), because the two state machines
have different shapes: Payroll's has a real branch (`reject()` sends
`ForApproval` back to `ForReview`, not forward), so each transition
needs its own validation and audit columns. Offboarding's blueprint
diagram is strictly linear with no branch at all except an app-level-
added Cancel off-ramp — ten identical `abort_unless($status ===
X)->update(['status' => Y])` methods would be pure repetition of the
same logic ten times. `sequence()` returns the ten non-Cancelled cases
in pipeline order; `next()` looks up the current case and returns
whichever follows (or `null` at `Separated`); `advance()` just resolves
`next()`, aborts if there isn't one or the request is already terminal,
and updates `status` + `status_changed_at` (plus `approved_at`/
`approved_by` at the `Approved` step specifically, the one step with
its own audit columns). `isTerminal()` is `Separated || Cancelled`.

**The one wired integration: reaching `AccountDisabled` disables the
employee's linked `User` account**, reusing Phase 3's existing
`users.disabled_at` column and disable mechanism verbatim
(`$employee->user?->update(['disabled_at' => now()])`) — an employee
with no linked account (most employees; `users.employee_id` is optional,
Phase 12c) simply has nothing to disable, which is correct, not an
error. Every other pipeline step is deliberately left as a documented
gap rather than a fake integration:
- **Final Payroll / Final Pay** don't create or touch any `PayrollPeriod`/
  `PayrollItem` — computing a final pay run (prorated last-period salary,
  unused leave conversion, government-mandated final pay components) is
  real, jurisdiction-specific payroll policy, not a mechanical status
  flip, the same restraint CLAUDE.md's Payroll section already documents
  for overtime/holiday pay peso amounts.
- **COE** doesn't auto-create a `CoeRequest` (Phase 13d already has a
  full request→approve→snapshot→PDF flow of its own) — advancing past
  this step records that a COE was handled, but HR still submits the
  actual request through the existing Employee Self-Service flow if the
  separating employee needs the document.
- **Separated** doesn't create an `Employment` row with `change_type`
  reflecting separation, or set an end date on the current one —
  `Employment`'s own separation `change_type` exists in
  `EmploymentChangeType` but nothing calls it from here. Wiring that in
  would mean either guessing an `effective_date` this table doesn't
  collect or adding a new field just for this integration; better to
  leave "record the actual separation" as an explicit follow-up HR
  action through the existing Employment tab (Phase 7) than to guess.

**Never deletes the `Employee` record**, per blueprint §26's own
explicit note and CLAUDE.md's own non-negotiable rule — there is no
`destroy()` on `OffboardingRequestController` at all, matching
`EmploymentController`'s append-only shape rather than
`Admin\AttendanceController`'s corrected-in-place shape (there's nothing
here that needs correcting after the fact the way a mistyped attendance
time does).

**At most one non-terminal request per employee at a time**, checked
with the same `whereNotIn('status', ['separated', 'cancelled'])->exists()`
shape `hasPendingJobOffer()`/onboarding's "at most one incomplete
checklist" checks already use — a fresh request can always be started
again after a prior one was `Cancelled` (an employee withdrawing their
resignation and later resigning again is a normal scenario, not an
error state).

**`cancel()` requires a reason** (`cancellation_reason`, same required-
string pattern as `LeaveRequestController::reject()`/`PayrollPeriodController
::reject()`) and works from any non-terminal status, not just early
ones — an offboarding can be called off at Clearance or Asset Return
just as validly as at Resignation, so there's no restriction on *which*
non-terminal status Cancel is available from beyond "not already
terminal."

Gated by `employees.view` (index, same as COE Requests' flat list) and
`employees.update` (store/advance/cancel, same as every other per-
employee mutating sub-resource) — no dedicated `offboarding.*`
permission group exists in the seeded catalog, and this is fundamentally
an employment-lifecycle action on an existing `Employee`, the same shape
`employees.update` already gates for Employment/Compensation/Onboarding,
so it reuses rather than invents, the same move Compensation (Phase 10)
made first.

Managed from a new **Offboarding** tab on the employee profile page
(`admin.employees.show`) — a "Start Offboarding" button/modal when no
request is active, a status card with "Advance to {next step}" and
"Cancel" actions when one is, and a read-only History table below of
every request the employee has had. Also gets a genuine new **WORKFORCE
> Offboarding** sidebar entry pointing at the flat
`admin.offboarding-requests.index` list (all in-progress and completed
requests across every employee, for HR to triage from one place) — a
real, deliberate addition, unlike Career/Succession (Phase 15g), which
got no sidebar entry of their own since they're purely per-employee
detail with no company-wide list HR needs to scan.

**Bug caught by browser verification: none in the application** — both
issues found during this slice's Playwright pass were in the
verification script, not the app itself. First, the standard tab-
reselection issue this codebase has hit before (a Bootstrap tab resets
to its default after any full-page redirect; the advance-loop needed to
re-click the Offboarding tab on every iteration, not just once at the
top). Second, a new variant of the same class: Laravel's one-time flash
`session('status')` was being consumed by the tab-reselection helper's
own extra `page.goto()` navigation *before* the script ever checked
`.alert-success`, since that check ran after an additional re-navigation
past the redirect that already carried the flash. Fixed by checking the
success message immediately after the redirect's own page load, before
any further navigation. Ground-truthed against the real sequence after
both fixes: all ten steps advanced correctly end-to-end, `approved_at`/
`approved_by` were stamped at `Approved`, and the linked `User` account's
`disabled_at` was confirmed non-null via `tinker` after `AccountDisabled`.

## Security Hardening (Phase 17, in progress)

Blueprint §54's Phase 17 is "Security Hardening & OWASP Verification"
(§49-53: OWASP Top 10:2025, ASVS 5.0, a long per-topic checklist, and a
final verification matrix). CLAUDE.md's own header already states the
governing principle: **security is built continuously, not bolted on
here** — RBAC, data scope, MFA, session security, CSRF (Laravel's
default), object-level payslip/document ownership, login throttling,
and login/failed-login/payslip-access logging are all already in place
from Phases 3/4/12/13. Phase 17 is verification of those controls
(finding and closing real gaps) plus the handful of genuinely new
cross-cutting pieces blueprint §51 calls for that nothing earlier had a
reason to build yet. Built as sub-slices like every other multi-part
phase.

**17a — Security headers.** A new `App\Http\Middleware\SecurityHeaders`,
appended to the `web` middleware group in `bootstrap/app.php` (not a
route-level alias like `auth.session`/`mfa.superadmin` — headers belong
on every browser-facing response including guest/login/error pages, not
just authenticated ones), sets Content-Security-Policy,
X-Content-Type-Options, X-Frame-Options, Referrer-Policy,
Permissions-Policy, and — conditionally, only when `$request->secure()`
— Strict-Transport-Security (blueprint §51 17.13 explicitly scopes HSTS
to "where appropriate"; sending it over plain HTTP in local/sandbox dev
would be actively wrong, not just unnecessary).

**The CSP's `script-src`/`style-src` include `'unsafe-inline'`, and
`script-src` also includes `'unsafe-eval'` — a deliberate, documented
trade-off, not an oversight.** Blueprint §51 17.14 explicitly warns
"Do not blindly copy a CSP from another application. Tune it for the
actual frontend resources" — so before writing the policy, the actual
frontend was audited: `layouts/partials/head.blade.php` has a genuine
inline `<script>` that sets `data-bs-theme` before first paint (avoiding
a flash of the wrong theme, can't wait for the bundled JS to load);
~10 admin views use inline `onchange="this.form.submit()"` on filter
`<select>`s; ~80 views use inline `style="width: …%"` for progress bars
(Onboarding/Training/PIP-adjacent cards); and Alpine.js (`x-data`/
`@click`, used on the 2FA challenge and Security pages) evaluates its
directive expressions via `new Function()` internally, which CSP's
`'unsafe-eval'` restriction exists specifically to block. A strict
policy omitting these would silently break the theme toggle, every
auto-submit filter dropdown, and both Alpine interactions — CSP
violations fail silently in the browser console, not as a visible error,
so shipping a policy that merely *looks* strict without checking against
real usage would be worse than an honest one: a false sense of
protection. The real fix for each (nonce the theme script, convert
`onchange` attributes to `addEventListener`, migrate to the restricted
`@alpinejs/csp` build and rewrite every existing directive) is a genuine
follow-up, not done here — noted rather than silently deferred, the same
restraint CLAUDE.md already applies to Leave's accrual scheduler and
Payroll's overtime-pay gap. `object-src 'none'`, `base-uri 'self'`,
`form-action 'self'`, and `frame-ancestors 'self'` are all real,
unweakened restrictions — nothing about this app needed them loosened.

**Verified with Playwright, not just by reading the policy string**:
logged in as a plain non-Superadmin test user (Superadmin would have
redirected to `/security` for mandatory MFA setup before reaching any of
the pages under test, per `EnsureSuperadminHasTwoFactorEnabled` —
irrelevant to what this slice needed to check), a console listener
watching for `Content Security Policy`/`Refused to` messages across
login, an Alpine `@click` interaction, and an `onchange` auto-submit
filter that visibly re-navigated with a query string — zero violations
across all of them, confirming the policy neither over-blocks the real
frontend nor is silently doing nothing.

`SecurityHeadersTest` (`tests/Feature/Security/`, the same directory
`SessionManagementTest` already established in Phase 3) pins down the
headers present on both a guest and an authenticated route, the
HSTS-only-over-HTTPS behavior specifically (simulated in-test by
requesting an `https://` URL, which Symfony's `Request::create()` reads
the scheme from directly rather than needing a manual server-variable
override), and that `object-src`/`base-uri`/`frame-ancestors` are
genuinely restricted in the emitted policy string.

**Not addressed by this slice**: removing the `X-Powered-By`/`Server`
response headers PHP-FPM/nginx add by default — that's `expose_php =
Off` and `server_tokens off`, infrastructure-level config that belongs
to Phase 18 ("Nginx/PHP-FPM" deployment), not something a Laravel
middleware can control.

**17b — Generic Audit Log.** Blueprint §51 17.16's worked example
(`User: John Smith / Action: UPDATE / Module: Employee Compensation /
Before: ₱30,000 / After: ₱35,000`) is a cross-module security trail
distinct from every log this app already has: `login_logs` (Phase 3),
`attendance_correction_logs` (Phase 8), `leave_transactions` (Phase 9),
and `payslip_access_logs` (Phase 12c) each cover one module's own
concern, but nothing gave a security reviewer one place to see every
sensitive change across the app. A new `audit_logs` table (`user_id`
nullable — a console/system action has none; `action`, an
`App\Enums\AuditAction` string-backed enum with a `label()`, the same
"enum owns its display string" idiom `OffboardingStatus`/`BenefitType`
already established; `module`, a plain string label like blueprint's own
example, not an enum — it's freeform display text with no behavior
attached, unlike `action`; polymorphic `auditable_type`/`auditable_id`;
`before`/`after` JSON; `ip_address`; `created_at` only, no `updated_at`)
plus `App\Domain\Security\Services\AuditLogger::log()`, the one place
that writes a row, following the same "Security" architectural leg
blueprint §48's own diagram draws alongside Authentication/Authorization
— `DataScopeResolver` (Phase 13e) already lives in
`App\Domain\Security\Services\`, so this does too.

**`before`/`after` are small, hand-picked scalar arrays, never a raw
model dump.** Blueprint's own example logs exactly one field
(`basic_salary`), not every column on the row — a full-model diff would
bury the one meaningful change under boilerplate (`updated_at` touches,
unrelated columns) and risk leaking fields a reviewer has no business
seeing in a security log. Every call site is responsible for passing
only plain strings (arrays like a role list are joined with
`implode(', ', ...)` before logging, e.g. `'roles' => implode(', ',
$newRoles) ?: '(none)'`) — the index view's `{{ }}` echo of `$log->before
[$field]`/`$log->after[$field]` would break on a nested array, so this
discipline is enforced at the write side rather than papered over with
defensive casting in the view, matching this codebase's general
preference for fixing correctness at the source.

**Wired at eight call sites chosen to match blueprint §51 17.24's
"Sensitive Operations" list directly, not applied blanket via a model
observer.** A generic "audit every model change" observer was considered
and rejected: it would log every trivial internal update indiscriminately
(no way to distinguish "sensitive" from routine without per-model
configuration anyway) and bury the log's real signal under noise —
explicit calls at named sensitive mutation points is the same judgment
call CLAUDE.md's Attendance section already made choosing direct
`AttendanceCorrectionService::apply()` calls over a blanket model-event
approach. Wired: `UserController::store()`/`updateRoles()`/`disable()`/
`enable()` ("User creation," implicit in "Role changes," and account
status changes); `RoleController::store()`/`update()` ("Role changes,"
"Permission changes" — `update()`'s action is
`AuditAction::PermissionsChanged` specifically, capturing the role's
permission list and `data_scope` before/after); `EmploymentController
::store()`, but *only* when the new row's `basic_salary` actually
differs from the prior current row's ("Salary changes" — Employment
itself, per CLAUDE.md's own Employment section, is already the real
append-only source of truth for compensation history; this is a
secondary entry for the cross-module security view, not a second
history mechanism, so a same-salary employment change like a
regularization or transfer correctly writes no entry at all, confirmed
by `AuditLoggingTest::test_an_employment_change_without_a_salary_change
_writes_no_audit_entry()`); and `PayrollPeriodController::finalize()`
("Payroll finalization"). Left as documented gaps rather than sprinkled
everywhere: password/MFA changes and login/logout already have their
own dedicated coverage (`SecurityAlert` emails, `login_logs`) that this
table would only duplicate; document downloads and bulk exports have no
generic audit entry yet since neither blueprint's example nor 17.24
singles them out as urgently as the eight wired here.

**No `update()`/`destroy()` exist on `AuditLogController` at all** — the
same "protection by omission" `EmploymentController`'s append-only shape
and finalized `PayrollPeriod` already rely on, satisfying blueprint's
"Audit logs should be protected from ordinary modification and
deletion" without needing a model-level guard for a code path that
can't be reached in the first place.

**Gated by `audit-logs.view`, reserved in the seeded catalog since Phase
4 but — like `organization.manage` before it — granted to no role by
default.** Superadmin's `Gate::before()` bypass covers access today;
deciding which roles should see a company's full security audit trail
is a real judgment call for whoever deploys this, not one to make
speculatively here. Lights up the ADMINISTRATION > Audit Logs sidebar
placeholder. The index page filters by module/action via the same
`onchange="this.form.submit()"` auto-submit pattern used throughout the
admin views, paginated, newest first.

**Verified with Playwright, including one script-only bug that produced
a genuinely confusing false negative before being traced to its real
cause.** Filtering the index by module appeared to show zero rows
immediately after `selectOption()`, while a slightly later assertion on
the same page state showed the row correctly present — `page.selectOption()`
resolves once the `<select>`'s value updates, but does not wait for the
navigation the `onchange="this.form.submit()"` handler goes on to
trigger, so an assertion made immediately afterward can race a
form-submission navigation that hasn't started yet, reading stale
pre-navigation state (confirmed by checking `page.url()` at the same
point: it still showed the un-filtered URL with no `?module=` query
string at all). Fixed by wrapping the select in `Promise.all([page
.waitForNavigation(), page.selectOption(...)])` so the navigation wait
is registered before the action fires, rather than after — the same
family of issue as this project's established tab-reselection and
flash-message-timing lessons (a Playwright assertion racing an
async page transition), not a new class of bug, but a new trigger for
it (a plain `<select>` auto-submit rather than a full-page redirect).
Ground-truthed afterward: disabling a user through the real UI produced
a correctly-shaped row (actor, `Disabled` badge, `User Management`
module, `User #<id>` record, `disabled_at: (active) → <timestamp>`
change line) visible on the index page and survivable through the
module filter.

**17c — Broken access control / IDOR test suite.** Blueprint §51 17.4
and 17.5 name specific scenarios to test (`Employee → Other Employee`,
`Employee → Other Payslip`, `Employee → Payroll`, `Employee → HR
Documents`, `Manager → Other Department`, `HR Staff → Security
Settings`, `Payroll Staff → User Administration`; ID-tampering against
`/employees/100`, `/payslips/100`, `/documents/100`, `/payroll/100`,
`/leave/100`) — this slice is purely test-writing, no application code
changed, since (per this file's own opening principle) the controls
being verified were already built as their owning phases landed: RBAC
permission gating, `DataScopeResolver`'s Team scope for Manager, and
per-record `employee_id === auth()->user()->employee_id` ownership
checks on every portal controller (documents, payslips, leave, COE).

**One file, `tests/Feature/Security/BrokenAccessControlTest.php`,
walks blueprint's literal scenario list end to end** rather than
scattering new assertions across existing test files — the
consolidation itself is this slice's value: a reviewer can read one
file top to bottom and check off every named item, even where deeper
behavioral coverage already exists elsewhere (`DataScopeTest` for
Manager/Team scope, `PayslipPortalTest` for payslip ownership) and only
gets one confirming boundary test here rather than a duplicated deep
suite. `test_changing_the_id_to_an_owned_record_still_succeeds` is a
deliberate positive control paired with the negative IDOR assertions —
a 404 is only meaningful proof of an ownership check if the same route
returns 200 for the actual owner; without it, a 404 could just as
easily mean the route or fixture was wrong.

**One real finding, not a vulnerability**: `HR Staff`/`Payroll
Administrator → User Administration` and `Employee → Payroll`/`→ HR
Documents` were all already correctly blocked purely by the seeded
permission catalog (`RoleAndPermissionSeeder::ROLES`) having never
granted `users.*`/`roles.*` to either role, and `Employee` never having
been granted `employees.view`/`payroll.*` — every assertion in this
file passed on the first run, confirming rather than fixing. Worth
recording precisely because a security-hardening phase finding nothing
to fix is itself the expected, successful outcome of CLAUDE.md's
"security is built continuously" principle, not a sign the phase did
nothing.

**17d — Input/file/CSRF verification, dependency audit, and final
documentation, closing Phase 17.** Blueprint §51 17.6/17.7/17.9/17.10/
17.11 named several more angles to check:

- **SQL injection (17.11)**: a repo-wide grep for `DB::raw`/`whereRaw`/
  `orderByRaw`/`selectRaw`/`havingRaw` and any request-driven `orderBy()`
  found zero matches — every query in the app goes through Eloquent's
  parameterized builder, so 17.11's specific worry ("dynamic SQL
  identifiers such as sorting fields — use allow-lists") doesn't apply:
  there's no raw fragment built from input anywhere to allow-list in
  the first place.
- **XSS (17.10)**: a repo-wide grep for Blade's raw-echo `{!! !!}`
  found exactly one use (`security/index.blade.php`'s 2FA QR code SVG,
  server-generated, not user input); every other output goes through
  `{{ }}`'s automatic escaping. `InputSecurityTest::test_a_note_
  containing_a_script_tag_is_rendered_escaped_not_executed()` pins this
  down against a real free-text field (an Employee Note, standing in
  for blueprint's "Comments"/"Notes" examples) rather than trusting the
  grep alone.
- **File upload (17.7)**: both upload paths already had solid
  validation from the phases that built them (`EmployeeDocumentController`:
  `mimes:pdf,jpg,jpeg,png,doc,docx`, `max:10240`; `ApplicantController`:
  `mimes:pdf,doc,docx`, `max:10240`), private `local` disk storage, and
  Laravel's default random storage filename (`UploadedFile::store()`,
  never `storeAs()` with a user-supplied name) — nothing to fix, but now
  pinned down by three new regression tests (rejects a disallowed type,
  rejects an oversized file, accepts a valid one) instead of resting on
  the validation rule alone.
- **CSRF (17.9)**: Laravel's `ValidateCsrfToken` ships in the `web`
  middleware group by default and `bootstrap/app.php` never excludes
  any route from it. A PHPUnit test asserting CSRF rejection would only
  prove something about the test environment, not this app — Laravel's
  own middleware short-circuits the check whenever
  `app()->runningUnitTests()` is true, specifically so feature tests
  don't need to thread a token through every `POST`, which is exactly
  why this whole suite's `$this->post(...)` calls have never needed
  one. What *is* this app's own responsibility, and was checked: every
  `method="POST"` Blade form actually carries `@csrf` — a repo-wide
  per-file count comparison (103 non-GET method-spoofed forms, 159
  POST forms total) found none missing it.
- **Dependency security (17.20)**: `composer audit` and `npm audit`
  both report zero vulnerabilities as of this slice.

**Production configuration (17.19) is a deployment-time concern, not
a codebase one, and is left to Phase 18 rather than faked here** —
`.env.example`'s `APP_DEBUG=true` is correct for its actual purpose (a
local-dev template; CLAUDE.md's own Stack section already documents
local/sandbox dev falling back to SQLite, the same "local needs
different defaults than production" reasoning), and there is no
mechanism in application code that could force `APP_DEBUG=false` in a
real deployment — that's an environment variable a deployer sets, which
is exactly Phase 18's "Production deployment" bullet.

**OWASP Top 10:2025 Verification Matrix** (blueprint §51 17.25):

| OWASP | Control | Verified by |
|---|---|---|
| A01 Broken Access Control | RBAC + data scope (Own/Team enforced), object-level ownership checks on payslips/documents/leave/COE | `BrokenAccessControlTest`, `DataScopeTest`, `PayslipPortalTest` |
| A02 Security Misconfiguration | `SecurityHeaders` middleware; `APP_DEBUG=false` required in production (deploy-time, Phase 18) | `SecurityHeadersTest` |
| A03 Software Supply Chain Failures | `composer audit` / `npm audit`, both clean | 17d (manual run) |
| A04 Cryptographic Failures | bcrypt password hashing, encrypted 2FA secrets, private document/payslip storage | Phase 3 auth test suite |
| A05 Injection | Eloquent-only queries (zero raw SQL), Blade auto-escaping (one trusted, non-user-input exception) | 17d grep audit, `InputSecurityTest` |
| A06 Insecure Design | Payroll's maker-checker-shaped lifecycle (process → review → approve → finalize → lock → publish); permission catalog reserved ahead of each module | `PayrollLifecycleTest` |
| A07 Authentication Failures | Fortify MFA/throttling/generic errors; mandatory MFA for Superadmin; session invalidation | `Auth\*Test` suite, `SuperadminMfaTest` |
| A08 Software or Data Integrity Failures | Payroll immutable once `Finalized`; append-only `Employment`/`BenefitEnrollment`; generic audit log | `PayrollLifecycleTest`, `AuditLoggingTest` |
| A09 Security Logging & Alerting Failures | `login_logs`, `payslip_access_logs`, `audit_logs`, `SecurityAlert` notifications | `AuditLoggingTest`, `PayslipPortalTest` |
| A10 Mishandling of Exceptional Conditions | `DB::transaction()` around every multi-step write (Employment close+create, `LeaveBalanceService`, payroll processing); Laravel's default production error pages | transactional assertions throughout the Admin/Portal suites |

**OWASP ASVS 5.0** (blueprint §52) — a representative sample in
blueprint's own Requirement/HRIS/Test/Expected/Result shape, not an
exhaustive line-by-line pass against ASVS's full requirement list
(hundreds of items; blueprint's own body gives one worked example, not
a template implying full enumeration):

```
ASVS: Authentication -- Password Storage
HRIS: Passwords are hashed with bcrypt (Hash::make), never plaintext.
Test: Inspect users.password after registration/reset.
Expected: Bcrypt hash.
Result: PASS

ASVS: Authorization -- Object-Level Access
HRIS: An employee can only access their own payslip.
Test: Portal payslip show/download against another employee's payroll_item id.
Expected: 404.
Result: PASS (PayslipPortalTest, BrokenAccessControlTest)

ASVS: Session Management -- Session Invalidation
HRIS: Forcing logout on another session actually revokes its access.
Test: Force logout a second session, then reuse its session cookie.
Expected: Access denied, redirected to login.
Result: PASS (SessionManagementTest)

ASVS: Input Validation -- File Upload
HRIS: Document upload rejects disallowed MIME types and oversized files.
Test: Upload a .php file; upload a file over the 10MB limit.
Expected: Validation error, nothing stored.
Result: PASS (InputSecurityTest)

ASVS: Access Control -- Data Scope
HRIS: A Manager can only act on their direct reports' requests.
Test: Manager attempts to approve a non-report's leave request.
Expected: 403.
Result: PASS (DataScopeTest)
```

**Phase 17 Final Security Checklist** (blueprint §53) — checked
against what this codebase actually contains, not aspirationally:

Authentication: password hashing, MFA, password reset, login
throttling, brute-force protection, session security, account
lock/disable, and re-authentication for sensitive actions are all done
(Phase 3/4).

Authorization: RBAC, permissions, policies, and data-level authorization
are done; Manager (Team) and Employee (Own) scope are enforced (Phase
13e). Department/Branch/Company/All scope columns exist but enforcement
is deliberately unbuilt — no seeded role exercises them, and CLAUDE.md's
own Authorization section already documents this as a "don't build for
scopes nothing uses" restraint, not an oversight.

Application: CSRF, XSS, SQL injection, input validation, output
encoding, file security, and secure error handling are all done and
verified this phase. Path traversal: not independently tested, but
structurally unreachable — every file operation goes through Laravel's
`Storage` facade and `UploadedFile::store()`'s own generated paths,
never a user-supplied path segment.

Data: encryption (password hashes, encrypted 2FA secrets), sensitive
data protection (salary gated by `employees.salary.view`, payslips by
ownership), private file storage, payroll snapshots, and historical
records (effective-dated, never overwritten) are all done.

Infrastructure: security headers are done (17a). HTTPS/firewall/secure
server/database restrictions/secrets management/production
configuration are deployment-environment concerns Phase 18 ("Production
deployment," "HTTPS," "Nginx/PHP-FPM") owns — nothing here for an
application codebase to build ahead of an actual target server.

Monitoring: audit logs (17b), security-event emails, login logs, and
data access logs are all done. Broader alerting/error-monitoring
integration (e.g. a Sentry-style service) is a Phase 18 concern — this
codebase logs to its own tables/notifications, not to external
monitoring infrastructure that doesn't exist yet.

Supply chain: `composer audit`/`npm audit` are clean as of this
session; dependency updates and package review are an ongoing
maintenance practice rather than a one-time checkbox. CI/CD security has
no pipeline to secure yet — also Phase 18.

OWASP: Top 10:2025 and ASVS 5.0 verification are both done above.
Authentication/Session/Authorization/File Upload/Logging/Secure Headers
guidance are all reflected in the controls already cited throughout
this file.

Testing: unit/feature/authorization/payroll-calculation/integration/
security tests are all done (387 tests as of this slice, including the
five new `Tests\Feature\Security\*` files this phase added). Penetration
testing and automated vulnerability scanning are **not done and can't
honestly be claimed done from inside a coding session** — both need a
live deployed target and tooling/expertise outside this codebase; `composer
audit`/`npm audit` are the automated-dependency-scanning slice of this
that a codebase-level check *can* do, and that was done. Backup
restoration testing is Phase 18's "Restore testing" bullet, which needs
an actual backup/restore pipeline to exist first.

Phase 17 (Security Hardening & OWASP Verification) is complete for
everything a coding session can build and verify: security headers
(17a), a generic audit log (17b), a broken-access-control/IDOR test
suite (17c), and input/file/CSRF verification plus this closing
documentation (17d). What remains — infrastructure hardening, CI/CD,
monitoring integration, and third-party penetration testing/vulnerability
scanning — is Phase 18's job or a real deployment's own security review,
not a gap in this phase's own scope.

## Production, Backup & Disaster Recovery (Phase 18, complete)

Blueprint §54's final phase. Unlike every prior phase, most of its
bullets (Nginx/PHP-FPM, monitoring, CI/CD, disaster recovery) describe
*infrastructure and process* a real deployment needs, not application
features — this phase is built the same sub-slice way as every other
multi-part phase, but expect a higher proportion of config/scripts/docs
alongside application code than usual, and expect some bullets to stay
documented process rather than runnable code, the same honesty this
file already applies to things a coding session can't self-certify
(Phase 17d's penetration testing / vulnerability scanning gap).

**18a — Scheduler.** Blueprint §47 names ten things to automate; this
slice builds the two blueprint explicitly names first and that CLAUDE.md
had already flagged, in Leave's and Training's own sections, as waiting
for exactly this moment: **Leave accrual** and **certificate expiration
reminders**. Neither `app/Console/` nor `routes/console.php`'s scheduler
had ever been used before this slice (`php artisan schedule:list`
reported zero tasks) — Laravel 12's skeleton needs no `Kernel.php` or
`bootstrap/app.php` change, just `Schedule::command(...)` calls directly
in `routes/console.php`, which is where these three now live.

`App\Console\Commands\AccrueLeaveBalances` (`leave:accrue`, scheduled
daily at 01:00) walks every active `LeavePolicy` and self-gates *which*
ones are actually due against today's date — `Monthly` fires on the
1st, `Annually` fires only on January 1st — rather than relying on the
cron cadence alone, since a single daily trigger has to serve both
frequencies correctly. Accrual is capped at the policy's `max_balance`
when set (accruing the smaller of the policy's rate or the room left
below the cap, and skipping entirely once a balance is already at the
cap) rather than blindly adding the full rate every time. Employees are
resolved as every active (`archived_at IS NULL`) employee of the
policy's `company_id` — `LeavePolicy` has no finer-grained per-employee
assignment table (confirmed by grep before designing this), so "every
employee at this company" is the only consistent interpretation
matching how `LeaveBalance` itself is keyed by `(employee_id,
leave_type_id)` alone, with no `leave_policy_id` column to narrow by.

**`AccrualFrequency::PerPayPeriod` is a deliberate, documented gap, not
silently skipped** — `isDueToday()` returns `false` for it outright.
Firing it correctly needs each employee's own `PayrollGroup` pay
frequency (weekly/biweekly/semi-monthly/monthly, set on `Employment`,
not on `LeavePolicy` at all) to know which days are real period
boundaries, which would mean re-deriving Payroll's own period logic
inside the Leave domain without a concrete requirement for how the two
should actually line up — the same restraint this app already applies
to overtime/holiday pay rates and Benefits' SSS/PhilHealth/Pag-IBIG
consolidation.

**`App\Console\Commands\CarryOverLeaveBalances` (`leave:carry-over`,
scheduled `yearlyOn(1, 1, '01:30')`)** is the natural pairing
`LeaveTransactionType::CarryOver` was reserved for back in Phase 9
("exist in the ledger's vocabulary for when that job is built," written
about `Accrual` and `CarryOver` together). A policy's `carry_over_days`
is a *cap* on what survives into the new year, not an amount to add —
any balance above it is forfeited down to the cap (logged as a negative
`CarryOver` transaction so the ledger shows exactly what was lost and
why); a balance at or below it is left untouched, and a policy with no
`carry_over_days` set is skipped entirely (unlimited carry-over, nothing
to cap). Unlike `AccrueLeaveBalances`, this command does no internal
date-gating — it has exactly one frequency, so the schedule entry's own
`yearlyOn()` is the only date check needed.

**`App\Console\Commands\SendTrainingCertificateExpirationReminders`**
(scheduled daily at 07:00) closes the gap 15f's CLAUDE.md section
documented almost verbatim ("a natural candidate whenever this app
grows a job-scheduling story for the first time" — this is that
moment). Fires at exactly two thresholds, 30 and 7 days before
`certificate_expires_at`, matched by exact date
(`whereDate('certificate_expires_at', today+N)`) rather than a "less
than N days away" range — that sends each reminder exactly once per
enrollment without needing a new "already reminded" tracking column,
the same reasoning `TrainingEnrollment` itself already avoided a
separate attendance table for. A new `TrainingCertificateExpiring`
notification (`Queueable`-but-not-yet-`ShouldQueue`, matching
`PayslipPublished`'s and `SecurityAlert`'s existing shape until 18b)
goes only to employees with a linked `User` account, silently skipping
unlinked ones — the same pattern `PayrollPeriodController::publish()`
already established for `PayslipPublished`.

**Bug caught: none.** All 14 new tests
(`tests/Feature/Console/{AccrueLeaveBalancesTest,
CarryOverLeaveBalancesTest,SendTrainingCertificateExpirationRemindersTest}.php`)
passed on the first run, and `php artisan schedule:list` confirmed all
three entries register with the intended cadence before any test was
even written.

**18b — Queue workers.** Blueprint §46 lists email/bulk notifications
among what should be queued. All three of this app's `Notification`
classes (`SecurityAlert`, `PayslipPublished`, and 18a's new
`TrainingCertificateExpiring`) already used the `Queueable` trait every
notification gets by default, but none implemented `ShouldQueue` --
meaning every one of them actually sent synchronously, inline in
whatever request or command triggered it, despite carrying the
plumbing to do otherwise. All three now implement `ShouldQueue`.

**Verified as genuinely queued, not just interface-compliant** --
`QueuedNotificationsTest` pins down the `implements ShouldQueue` change
itself (a real regression against it quietly being dropped later), but
that alone doesn't prove queuing actually *works*, since `phpunit.xml`
forces `QUEUE_CONNECTION=sync` for every test (matching current
behavior exactly, which is *why* zero existing tests broke from this
change -- `sync` still runs a job's handler inline). The genuine,
non-test proof: triggering `$user->notify(new SecurityAlert(...))`
against the real local `.env` (`QUEUE_CONNECTION=database`, per
CLAUDE.md's own Stack section) inserted a real row into the `jobs`
table rather than sending immediately; running `php artisan queue:work
--once --stop-when-empty` against it completed the job and removed the
row, with nothing landing in `failed_jobs`.

**Queue *workers* -- the actual always-running `queue:work` process a
deployment needs -- are an infrastructure concern, not a code one, and
are deliberately deferred to 18d** rather than half-built here: a
Supervisor/systemd unit definition belongs alongside 18d's other
deployment config (Nginx, PHP-FPM) as one coherent set, not
fragmented across two separate slices. Until that worker process
exists in a real deployment, queued notifications would sit in `jobs`
until something processes them -- true of any Laravel app's queue
system, not a gap specific to this one, and exactly why blueprint lists
"Queue workers" as its own bullet alongside "Use Laravel queues" as a
separate one.

**Fully async payroll processing / PDF generation / bulk exports --
also named in blueprint §46 -- stay synchronous, a deliberate,
documented gap, not an oversight.** Converting `PayrollCalculationService
::process()` or payslip PDF generation into queued jobs is a real UI/UX
change (the admin currently gets an immediate "processed" result;
async would need a "processing..." state and a way to learn when it's
done -- polling, a notification, or similar), not a one-line
`ShouldQueue` addition the way notifications were. Building that
without a concrete requirement for how the UI should reflect
in-progress async state would be guessing at a feature, not hardening
a deployment -- the same restraint this app already applies to
overtime/holiday pay rates and `PerPayPeriod` accrual (18a). A natural
candidate whenever a future phase actually needs payroll processing to
survive a request timeout on a very large company, not before.

**18c — Backup, encryption, and restore testing.** Blueprint §51
17.22 states the principle this whole slice is built around: "A backup
that has never been restored is not a proven backup." Three new
commands, deliberately hand-built rather than a third-party backup
package (blueprint's actual ask -- three payloads, encrypted,
checksummed, restorable -- is narrow enough to stay legible end to end
as plain code, the same "collapse to what's real" judgment this app
has made everywhere else rather than reaching for a heavier dependency
by default):

- **`backup:run`** writes three independently encrypted payloads --
  the database (raw SQLite file bytes, or a `mysqldump` for MySQL,
  shelled out via Laravel's `Process` facade), a zip of
  `storage/app/private/` (every employee document and applicant resume
  currently on disk), and `.env` itself (blueprint's "Configuration
  Backup," the one payload that's actively dangerous to leave
  unencrypted since it holds `APP_KEY` and database credentials) --
  plus a `manifest.json` recording each payload's SHA-256 checksum.
  Encryption is Laravel's own `Crypt` facade (AES-256-CBC with a MAC,
  keyed by `APP_KEY`) applied per-payload, not a separate backup-
  specific key: whoever can decrypt a backup already needs equivalent
  access to the app's own environment, so a second secret to manage
  would add complexity without a real security boundary behind it.
- **`backup:restore {backup-dir} {output-dir}`** decrypts each payload
  and verifies its checksum against the manifest before writing
  anything out, failing cleanly (not a raw stack trace) if a payload
  was corrupted or tampered with. Deliberately does **not** swap the
  restored files into the live database path or `storage/app/private/`
  itself -- doing that safely needs the application stopped first (a
  live SQLite file can't be safely overwritten out from under an open
  connection; MySQL needs an actual maintenance window), which is a
  deployment *procedure* decision belonging in 18d's disaster-recovery
  runbook, not something an automated command should silently do. This
  command's job ends at "here is the verified, decrypted, restorable
  content."
- **`backup:verify-latest`** is blueprint §47's own "Backup
  verification" scheduler bullet, built as real automation rather than
  a one-time manual check: finds the most recent `backup:run` output,
  restores it into a throwaway scratch directory (relying on
  `backup:restore`'s own checksum verification to catch corruption),
  and deletes the scratch copy either way -- its job is proving the
  backup restores cleanly, not producing a usable copy. Scheduled 30
  minutes after `backup:run` itself, so it always checks the backup
  that was *just* taken.

**Ground-truthed against real application data, not just the test
fixtures.** Before writing the formal test suite, `backup:run` was run
against this sandbox's actual dev database and `storage/app/private/`
contents, then `backup:restore`'d into a scratch directory: the
restored SQLite file's SHA-256 checksum matched the live database file
*exactly*, and querying the restored copy directly via PDO returned
real, correct data. One apparent discrepancy surfaced during this
check -- a raw `SELECT COUNT(*) FROM employees` against the restored
file returned more rows than `Employee::count()` against the live
app -- and rather than assuming either a backup bug or writing it off,
it was traced to its actual cause: `Employee` uses `SoftDeletes`,
so Eloquent's `count()` silently excludes soft-deleted rows that a raw
SQL count does not. Confirmed by re-running both counts with an
explicit `WHERE deleted_at IS NULL`, which matched exactly -- a
verification-methodology gap on the first check, not a real defect,
the same "confirm before concluding" discipline this project has
applied to every surprising result all session.

**Testing needed real file fixtures, not the app's own default paths**
-- `phpunit.xml` runs the whole suite against an in-memory
(`:memory:`) SQLite connection specifically for speed, which has no
file on disk to back up at all. Rather than slow down the whole suite
by switching its default connection, `backup:run`/`backup:verify-latest`
gained `--database-path`/`--source-dir`/`--env-path`/`--backups-root`
overrides (defaulting to the real paths when omitted) so
`BackupRestoreTest` can exercise the real file-based logic path
against temporary fixtures without touching either the shared test
database or this app's real dev files. 11 new tests across
`tests/Feature/Console/BackupRestoreTest.php`, all passing on the first
run once the override options existed.

**Deliberately not built**: backup retention/pruning (old backups
under `storage/app/backups/` accumulate indefinitely -- a real
deployment would want a "keep last N days" policy, not built
speculatively here without a concrete retention requirement to build
against) and streaming encryption for very large databases
(`Crypt::encryptString()` holds the whole payload in memory; a
genuinely large production database would need to pipe `mysqldump`
through something like `openssl enc` instead). Both are real, sized
follow-ups, not silent gaps.

**18d — Production deployment config, CI/CD, and the deployment/
disaster-recovery runbook, closing Phase 18 and blueprint §54's full
phase list.** Blueprint's remaining Phase 18 bullets (Nginx/PHP-FPM,
production deployment, disaster recovery procedure, CI/CD, deployment
procedure, rollback procedure) are mostly config and process, not
application code — this slice's own file mix reflects that.

**CI/CD was already done, not built here** —
`.github/workflows/tests.yml` (a `tests` job matrixed across PHP
8.3/8.4 against SQLite, plus a `lint` job running `vendor/bin/pint
--test`, both on every push to `main` and every pull request) has
existed since this project's very first commit, confirmed by `git log`
before assuming there was a gap to fill. Documented here, in
`DEPLOYMENT.md`, and in the Status bullet below rather than silently
rebuilding or re-claiming it.

**`deploy/`** holds three config files written for the actual target
this app's own code already assumes (PHP 8.3+, MySQL 8+/Redis in
production per the Stack section, `storage/app/private/` never
web-accessible per the Employee section) but genuinely untestable in
this environment — no `nginx`, `php-fpm`, or `supervisord` binary
exists here to validate syntax against, stated plainly in each file's
own header rather than silently presented as verified:
- **`nginx/hris.conf`** — HTTP→HTTPS redirect (leaving the ACME
  challenge path open), TLS termination, and a `location ~ \.php$`
  block marked `internal` so a client can never request an arbitrary
  `.php` file directly (blocking the "upload a file, then request it
  to get it executed" attack path at the web-server layer, independent
  of and ahead of the application's own upload validation) while the
  legitimate internal rewrite from `try_files` still reaches
  `index.php` normally. Explicitly does *not* duplicate the
  application's own security headers (`SecurityHeaders` middleware,
  17a) beyond HSTS on static-asset responses that never reach PHP at
  all — setting the same header at two layers risks them silently
  disagreeing later.
- **`php-fpm/hris.conf`** — a dedicated, unprivileged pool user (never
  `www-data` shared with other sites, never root — blueprint §51
  17.21's least-privilege principle applied to the OS process, not just
  the database user), `display_errors`/`expose_php` off, and an
  `upload_max_filesize`/`post_max_size` kept in sync with
  `EmployeeDocumentController`'s/`ApplicantController`'s own
  `max:10240` validation rules.
- **`supervisor/hris-worker.conf`** — the queue-worker *process*
  Phase 18b's `ShouldQueue` notifications explicitly deferred to this
  slice: two `queue:work` processes, auto-restarting, so queued
  notifications don't just sit in the `jobs` table indefinitely.

**`.env.production.example`** contrasts deliberately with
`.env.example` rather than replacing it — that file's local-friendly
defaults (`APP_DEBUG=true`, `MAIL_MAILER=log`, SQLite fallback
commented in) are correct for what it's for, and this file's
production-hardened ones (`APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`,
a real mail transport, MySQL/Redis required) are correct for what
*this* one is for; neither is a bug in the other.
`ProductionEnvironmentTemplateTest` parses it with the same `Dotenv`
library Laravel itself uses and pins down the values that would be a
genuine security regression if silently reverted later — most
pointedly `APP_DEBUG=false`, guarding in the one place a coding session
actually can enforce that blueprint §51 17.19 rule, since CLAUDE.md's
own Security Hardening section already explains why application code
itself cannot.

**`DEPLOYMENT.md`** is the step-by-step: initial deployment, deploying
an update, rollback (favoring forward-fixing over `migrate:rollback`,
consistent with this app's own "never overwrite historical data"
rules — most of its migrations are additive-only specifically so a
rollback is rarely the safe move), and disaster recovery (the actual
procedure for turning Phase 18c's `backup:restore` output into a
restored live system — stop the app, restore into a scratch directory,
verify, *then* sync into the live paths, matching exactly why
`backup:restore` itself deliberately stops short of doing that last
step automatically). It also names the one genuine gap honestly rather
than assuming it solved: this app has no off-host backup shipping
(encrypted backups stay on the same host under `storage/app/backups/`
unless something else moves them off it — a real requirement for
surviving the primary host itself failing, which needs knowledge of a
specific deployer's actual off-host storage this codebase can't
provide generically).

**Monitoring/error monitoring stays a documented, honest gap, not a
fabricated integration.** This app already provides real, working
audit trails with no external service needed (`login_logs`,
`payslip_access_logs`, `audit_logs`, three email notifications) — but
genuine uptime/error-rate/APM monitoring needs a real external service
(Sentry, Bugsnag, a hosted log aggregator) with its own account and
credentials no coding session can provision for an unknown future
deployer. `config/logging.php` already supports adding a monitoring
channel with zero application code changes once a deployer has
credentials — a configuration step, not a code gap.

Blueprint §54's full phase list (Phase 1 through Phase 18) is now
complete end-to-end. This does not mean every individual blueprint
bullet across all 18 phases has a built implementation — every
deliberate, documented gap called out along the way (Leave's
`PerPayPeriod` accrual, Payroll's overtime/holiday-pay rates, async
payroll processing, backup off-host shipping, external monitoring
integration, and others each phase's own section names) remains
exactly that: a real, sized, named follow-up for whoever picks this
codebase up next, not a silent omission. CLAUDE.md itself, not a
separate document, is the accounting of what's built versus what's a
documented, deliberate gap — read the phase section for the area being
touched before assuming either.

## Reports (Phase 19, complete)

Blueprint §3 lists eight report/analytics modules (items 53-60: HR,
Payroll, Attendance, Leave, Recruitment, Performance, Training Reports,
plus Workforce Analytics) and §55's own "V1 MVP" list names Reports as
item 19 of 21 — but, confirmed by grep before starting, blueprint never
gives Reports a numbered detail section (only "Workflow Engine" has one
among the modules named without a phase) and never assigns it to any of
§54's Phase 1-18 despite listing it as V1-scoped. Blueprint's own
V1/V2 split (§55) puts Reports in V1 and Recruitment/Performance/
Training/Benefits/Career/Succession in V2, but this project's actual
build order never gated on that split — Phases 14-16 built every one of
those "V2" modules well before Reports, a "V1" item, is being built now.
Not a correction to make retroactively, just a note that this project's
phase order was never meant to mirror blueprint's V1/V2 grouping. With
§54's own phase list fully built (see Status above), Phase 19 is this
session's own continuation past it, kept in the same lettered-slice
shape (19a, 19b, ...) every other multi-part phase already used.

**19a — Reports landing page + HR Report.** `Admin\ReportController::index()`
is the front door at `/admin/reports`, gated by `reports.view` — a
permission seeded since `RoleAndPermissionSeeder` was first written but,
confirmed by grep before this slice, never checked anywhere in the app
until now, the same "permission exists, nothing's granted/checked yet"
pattern `organization.manage` and `audit-logs.view` were before their
own phases. It's a card grid linking to each report type; a card shows
a real "View" link when the viewer both can reach the route and holds
its permission, or a "Coming soon" badge otherwise (`Route::has()` +
`can()`, the same check the sidebar itself already uses) — so the page
degrades correctly per-viewer rather than assuming every viewer sees
the same set.

**HR Report** (`Admin\HrReportController`, `admin.reports.hr.index`) is
new this slice: active/archived employee counts plus a company-
filterable breakdown by department, employment type, and status. Reads
straight through `Employee`/`Employment`/`Department` — no new tables,
the same "query existing data" restraint every report in this app
follows, `AttendanceReportController`/`LeaveReportController` (Phases
8/9) included. Grouping keys off `Employee::currentEmployment` (Phase
7's "exactly one source of truth for current" relation) with explicit
fallback labels (`'Unassigned'` for no department, `'unassigned'`/
`'no_current_employment'` for no current employment at all) rather than
silently dropping employees with no current employment row from the
breakdown entirely.

**Employment type/status display reuses the existing inline transform,
not a new `label()` method.** `EmploymentType`/`EmploymentStatus` have
no `label()` — this app's two existing displays of these exact enums
(`admin.employees.show`'s Overview and Employment tabs) already render
them with `ucwords(str_replace('_', ' ', $value->value))` inline in
Blade. The controller groups by the raw `->value` and the view applies
that same transform, rather than introducing a third way to display an
enum this app already has two consistent displays of — Phase 15g's
"enum owns its display string via `label()`" call was for a case
(`SuccessionReadiness`) where no existing convention produced correct
output; here one already does, so matching it is more consistent than
adding a second convention.

**`AttendanceReportController`/`LeaveReportController` keep their own
`attendance.view`/`leave.view` gates, unchanged** — both already existed
(Phases 8/9) and are reachable from their own module's subnav; this
slice only adds them to the sidebar's REPORTS section and this new
landing page as a convenience, gated in both places by the same
permission the controller itself already checks. Regating either to
`reports.view` would have been a silent access change (narrowing it for
Attendance Officer/whoever holds the module permission but not
`reports.view`, or widening it the other way) that nothing asked for.

**Sidebar REPORTS section** (previously five bare-string placeholders
since Phase 2) now has four real entries — `Overview` (the landing
page), `HR Reports`, `Attendance Reports`, `Leave Reports` — each gated
by the same permission its own controller checks, plus `Payroll
Reports`/`Analytics` staying disabled placeholders until 19b/19d. The
landing page's own "Overview" row avoids reusing the literal label
"Reports" a second time directly under the "REPORTS" section title,
the same reasoning `Organization`'s sidebar entry doesn't say
"Companies" even though that's the route it points at — the label names
the concept, not the route.

**Verified with Playwright against real seeded data**, logged in as a
throwaway HR Administrator account (not Superadmin — HR Administrator
holds `reports.view`/`leave.view` but deliberately not `attendance.view`,
confirmed against the seeder rather than assumed, which made a real,
correct negative case: the landing page's Attendance Reports card
correctly showed "Coming soon" for this viewer and the sidebar entry
was correctly disabled, not a bug): the landing page rendered all five
cards with the right link/badge split, the HR report's three breakdown
tables matched a hand-computed expectation across mixed seeded data
(employees with and without a current employment row, across two
departments), and the company filter's `onchange="this.form.submit()"`
auto-submit correctly re-filtered the page. No console errors.

**Bug caught: none in the application.** The one issue during
verification was in the Playwright script itself — `page.click('text=View')`
against a page with multiple "View" links didn't reliably land on the
intended card, fixed by asserting on each link's actual `href` and
navigating directly instead of clicking, a scripting fix, not an app
one.

**19b — Payroll Report.** `Admin\PayrollReportController`
(`admin.reports.payroll.index`) picks one `PayrollPeriod` (defaulting to
the most recent by `start_date`, company-filterable, any status —
Draft/ForReview data is shown same as Finalized, just like
`PayrollPeriodController`'s own index already does, since immutability
is about not overwriting Finalized data, not about hiding earlier-stage
data from `payroll.view` holders) and aggregates its `PayrollItem`s:
total gross earnings/deductions/tax/net pay, deductions grouped by
`PayrollItemLine.category`, government contributions grouped by agency
(employee and employer shares separately, matching how the admin period
detail page already shows both, unlike the payslip PDF which
deliberately only shows the employee share), plus a small recent-periods
table for a cross-period trend. No new tables, same "query existing
data" restraint as 19a. A "View period detail" link hands off to the
existing `PayrollPeriodController::show()` for the per-employee list
rather than duplicating it here.

**Gated by `payroll.view`, not `reports.view`** — the one deliberate
departure from 19a's HR Report precedent. `reports.view` is also held
by HR Administrator, who has no seeded access to any `payroll.*`
permission; gating aggregate payroll cost/deduction/contribution/tax
figures behind `reports.view` alone would hand that data to a role this
app otherwise keeps it from everywhere else (payslip ownership checks,
`employees.salary.view` gating on COE). Reuses the module's own
permission instead, the same choice `AttendanceReportController`/
`LeaveReportController` (Phases 8/9) already made for their own reports
— `reports.view` turns out to mean "can reach the Reports area and see
reports whose data has no more specific existing gate," not "can see
every report."

**Deduction category display reuses the same inline transform as
19a** (`ucwords(str_replace('_', ' ', $category))`) — `PayrollItemLine
.category` is free text set by `PayrollCalculationService`
(`'basic_salary'`, a `CompensationItemType` value, or an adjustment's
own category), the same raw-snake-case shape `EmploymentType`/
`EmploymentStatus` already had in 19a, so the fix applies for the same
reason. Caught by browser verification, not by the tests below (which
asserted on the raw grouped value, not the rendered label) — a real
instance of this project's own established lesson that Playwright
passes the tests don't reach, worth a second look here specifically
because a plain `assertViewHas()` on a controller's returned data can't
catch a view-layer formatting gap the way actually rendering the page
can.

**Verified with Playwright against real seeded payroll data** (two
periods, two employees each, contributions and deductions on every
item), logged in as a throwaway Payroll Administrator account: the
report defaulted to the more recent period, all four top-line totals
and both breakdown tables matched hand-computed sums exactly, the
period `<select>`'s auto-submit correctly switched periods (`Promise
.all([waitForNavigation(), selectOption(...)])`, the same fix 17b
established), and the period-detail link resolved to the real period.
No console errors.

**19c — Recruitment, Performance, and Training Reports.** Three more of
blueprint §3's eight (items 57-59). Blueprint's own admin nav sketch
(quoted at the top of this section) scaffolds only five REPORTS slots —
no Recruitment/Performance/Training row of its own, even though §3's
numbered list names all eight — so these three are real, fully-built
pages reachable only from the Reports landing page's card grid, not new
sidebar rows: the same "built, but not every built page gets its own
sidebar entry" precedent Interviews/Assessments (14c) and Career/
Succession (15g) already established, applied here to a whole report
rather than a per-employee tab. Each reuses its own module's `.view`
permission (`recruitment.view`/`performance.view`/`training.view`), the
same "reports.view is the fallback, not the rule" reasoning 19b's
Payroll Report established.

**`Admin\RecruitmentReportController`** shows an application-status
funnel (`ApplicationStatus::cases()` in pipeline order with zero-count
stages included, not sorted by frequency — a funnel reads correctly
only in process order) and a requisition-status breakdown, both
company-filterable, plus open-postings and hired counts.
`ApplicationStatus` already has a `label()` method (it was written with
one from Phase 14b), so the view calls `$row['status']->label()`
directly rather than the raw-value `ucwords(str_replace(...))` transform
19a/19b use for enums that don't — checked per-enum before writing the
view rather than assumed, since this app now has enums on both sides of
that line (see 19a's own note on `EmploymentType`/`EmploymentStatus`
lacking one).

**`Admin\PerformanceReportController`** picks one `PerformanceCycle`
(same "default to most recent, company-filterable" shape as 19b's
period picker) and reports its review count/average rating (rated
reviews only — a submitted-but-unrated review can't exist per 15b's own
`submit()` guard, but a `Draft` one still can), reviews by type, and
goal completion rate, plus a recent-cycles average-rating trend.

**`Admin\TrainingReportController`** aggregates across *all* enrollments
for the company rather than picking one session/course the way Payroll/
Performance pick a period/cycle — training has no single "current
period" spanning every course's sessions the way `PayrollPeriod`/
`PerformanceCycle` do, so a company-wide snapshot (19a's HR Report
shape) fits better here. Reports enrollment counts by status, an
overall completion rate, certificates issued, and certificates expiring
within 30 days — the same threshold `SendTrainingCertificateExpiration
Reminders` (18a) already uses for its first reminder, reused here for
consistency rather than picking a different window.

**A real bug, caught only by browser verification against real
(non-`RefreshDatabase`) data, not by the tests above.** Both
`PayrollReportController` and `PerformanceReportController` picked their
default period/cycle via `orderByDesc('start_date')->first()` alone; two
periods or cycles sharing the exact same `start_date` (a real
possibility — nothing prevents it, and the dev database already had two
`PerformanceCycle`s dated `2026-01-01`) made that pick genuinely
non-deterministic. `PerformanceReportTest`'s own fixtures always used
distinct dates, so nothing in the test suite could have caught this —
it only surfaced when Playwright loaded the real page against real data
and the freshly-seeded verification cycle wasn't the one selected.
Fixed by adding `orderByDesc('id')` as a tie-breaker on both controllers
(most-recent-by-date, then most-recently-created) and pinning it down
with a same-`start_date` regression test on each
(`test_payroll_report_breaks_a_tied_start_date_by_the_newest_period`/
`..._cycle`) rather than trusting the fix without one.

**Verified with Playwright against real seeded data** across all three
new pages plus the landing page's now-eight-card grid, logged in as a
throwaway account granted `recruitment.view`/`performance.view`/
`training.view`/`reports.view` directly (not through a seeded role): the
Recruitment funnel and requisition counts, Performance's review/goal
figures (re-verified correct immediately after the tie-break fix, not
just the fix compiling), and Training's completion rate/certificate
counts all matched hand-computed expectations against the seeded
fixtures, and the landing page correctly showed "Coming soon" for
Attendance/Leave/Payroll (this account genuinely lacks those three
permissions) alongside real links for the other four. No console
errors.

**19d — Workforce Analytics, closing Phase 19.** Blueprint §3 item 60,
and the fifth and last of the REPORTS slots blueprint's own admin nav
sketch scaffolds — the one this whole section's sidebar/landing-page
placeholder has been called "Analytics" since Phase 2. Deliberately a
single glance-level page, not an eighth detailed report:
`Admin\AnalyticsReportController` puts one top-line number from each of
the other reports side by side (active employees, open postings,
pending requisitions, pending leave requests, pending overtime
requests, average performance rating, training completion rate) —
Analytics' whole value is the side-by-side view, not a new breakdown,
so it deliberately doesn't re-derive anything 19a-19c's controllers
don't already compute in more detail.

**No payroll cost tile, on purpose.** "Workforce" analytics stays
headcount/people metrics — every number here is visible to anyone
holding `reports.view` alone (the same permission this page is gated
by, matching 19a's HR Report rather than 19b's `payroll.view`), and
19b already established why aggregate payroll figures need the
tighter gate `reports.view` alone doesn't provide. This page has no
per-tile permission mechanism (unlike the landing page's per-card
`Route::has()`/`can()` check), so adding one payroll-cost tile would
mean either loosening every `reports.view` holder's access to payroll
data or building a second gating mechanism this one page doesn't
otherwise need — a real, sized follow-up if a future slice wants it,
not silently deferred.

**Sidebar REPORTS section is now fully real** — all five items
(`Overview`, `HR Reports`, `Attendance Reports`, `Leave Reports`,
`Payroll Reports`, plus this slice's `Analytics`) point at working
pages, closing out the placeholder set Phase 2 first scaffolded. The
landing page's card grid is now a genuine eight-card front door to
every blueprint §3 report module (53-60), each card independently
showing "View" or "Coming soon" per the viewer's own permissions.

**Verified with Playwright against real seeded data**, logged in as a
throwaway account granted only `reports.view` (no module-specific
permission at all): the landing page correctly showed exactly two real
links (HR Reports and Analytics, the only two cards gated by
`reports.view` alone) against six "Coming soon" badges, the sidebar's
Analytics entry was enabled with the correct href, and all seven tiles
on the Analytics page matched hand-computed sums against seeded
leave/overtime/requisition/posting/review/enrollment fixtures. No
console errors.

**Bug caught: none in the application this slice** — the one real bug
this whole phase found (19b/19c's tied-`start_date` non-determinism)
was already fixed in 19c; nothing new surfaced writing the aggregate
queries here, likely because every query this slice needed was a
simpler version of one 19a-19c had already exercised and gotten
right.

Blueprint §3's eight report/analytics modules (items 53-60: HR,
Payroll, Attendance, Leave, Recruitment, Performance, Training
Reports, Workforce Analytics) are now built end-to-end across
19a-19d — the first phase this session added past blueprint §54's own
Phase 1-18 list, closing the "named in §3/§55 but never scheduled" gap
that started this phase. Every report reuses existing data with no new
tables, and every permission choice (`reports.view` as the shared
fallback; a module's own tighter `.view` permission — `attendance.view`/
`leave.view`/`payroll.view`/`recruitment.view`/`performance.view`/
`training.view` — wherever one already existed and the data was
sensitive enough to warrant it) is documented above per-slice rather
than applied as one blanket rule.

## Commands

```
composer install && npm install
cp .env.example .env   # then edit for your DB/Redis, or leave DB_CONNECTION=sqlite for zero-config local dev
php artisan key:generate
php artisan migrate
npm run dev             # or: npm run build
php artisan serve

composer test            # phpunit
vendor/bin/pint          # format; vendor/bin/pint --test to check only
```
