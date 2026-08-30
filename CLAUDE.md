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

No starter kit (Breeze/Jetstream) is installed. RBAC and everything past
Authentication is unbuilt — see Status below.

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
- Phase 3 — Authentication: login, logout, password reset, password
  change, 2FA (TOTP + recovery codes), login throttling, account
  status (`users.disabled_at`), login/logout/failed-login logging
  (`login_logs`), active-sessions list + force logout, password
  confirmation for sensitive actions, security-alert emails. See
  Authentication below before touching any of this.

**Not started:** Phase 4 (RBAC) onward — Organization, Employees, and so
on through Phase 18. Follow the phase order in blueprint §54/§59; don't
jump ahead to a later phase's tables/UI before its dependencies exist.
Re-read the relevant blueprint section before starting a phase — this
file is a summary, not a substitute.

Auth is deliberately not fully wired to RBAC yet: "Superadmin MFA
mandatory" (§17.2/§30) can't be enforced before a Superadmin role exists.
When Phase 4 lands, add that enforcement rather than re-deriving it.

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
