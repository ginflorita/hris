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

**Not started:** Phase 11 (Payroll Engine) onward, through Phase 18.
Follow the phase order in blueprint §54/§59; don't jump ahead to a later
phase's tables/UI before its dependencies exist. Re-read the relevant
blueprint section before starting a phase — this file is a summary, not
a substitute.

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
`RoleAndPermissionSeeder::ROLES`), but nothing queries it yet — Phase 5
(Organization) gives the hierarchy to scope *against*, Phase 6 (Employee)
gives Company-level scope something concrete to filter (every employee
carries `company_id`), and Phase 7 (Employment) is what actually records
*where* an employee currently sits (their `currentEmployment`'s
`department_id`/`branch_id`/manager chain) — the pieces now all exist,
but no query anywhere resolves a role's `data_scope` and filters by it
yet; that's still a real gap, not a false one.
When a Domain model needs it: resolve the acting user's effective scope
as the *broadest* among their roles for that permission (a user with both
a Team-scoped and a Company-scoped role gets Company for actions either
covers), then filter the query accordingly — don't build a fake example
against a model that doesn't exist.

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
