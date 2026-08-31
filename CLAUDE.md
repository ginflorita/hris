# HRIS

A modular Human Resources Information System. The full functional and
security specification is `docs/HRIS_Blueprint.md` — read it before working
on any module; this file only summarizes conventions and status.

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

- Phase 14 (partial) — Recruitment & Onboarding: **Job Requisitions**
  (headcount request, `pending → approved/rejected`, same request-then-
  decide shape as Leave/Overtime); **Job Postings** (`draft → published
  → closed`, only creatable against an *approved* requisition);
  **Applicants** (a candidate-pool profile, deliberately not company-
  scoped, with a private resume upload); and **Applications** (one
  applicant against one posting, a nine-stage pipeline status with two
  terminal exits). See Recruitment below for what's built and the rest
  of the pipeline still to come (Interviews, Assessments, Job Offers,
  hiring conversion into a real Employee record, Onboarding).

**Not started:** the remainder of Phase 14, then Phase 15 onward through
Phase 18. Follow the phase order in blueprint §54/§59; don't jump ahead
to a later phase's tables/UI before its dependencies exist. Re-read the
relevant blueprint section before starting a phase — this file is a
summary, not a substitute.

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

## Recruitment (Phase 14, in progress)

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

**Not built yet**: Interviews, Assessments, Job Offers, and the
hiring-conversion step where an accepted offer creates a real
`Employee` + `Employment` row (the actual integration point back into
Phase 6/7) — plus Onboarding (templates, tasks, per-hire completion
tracking), which depends on that conversion existing first.

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
