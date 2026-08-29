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

No starter kit (Breeze/Jetstream) is installed. Auth, RBAC, and everything
past the UI shell is unbuilt — see Status below.

## Architecture

```
app/
├── Actions/        cross-cutting single-purpose actions
├── Domain/          business logic by bounded context — see app/Domain/README.md
│   ├── Employee/ Attendance/ Leave/ Payroll/ Recruitment/
│   ├── Performance/ Training/ Benefits/ Workflow/ Security/
├── Enums/
├── Events/
├── Http/Controllers/   thin — delegate to a Domain service
├── Jobs/            queued work (payroll processing, PDF/payslip generation, bulk email)
├── Models/
├── Notifications/
├── Policies/         Laravel authorization policies
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
  `is_admin` boolean check.
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

**Not started:** everything from Phase 3 onward — Authentication, RBAC,
Organization, Employees, and so on through Phase 18. Follow the phase order
in blueprint §54/§59; don't jump ahead to a later phase's tables/UI before
its dependencies exist. Re-read the relevant blueprint section before
starting a phase — this file is a summary, not a substitute.

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
