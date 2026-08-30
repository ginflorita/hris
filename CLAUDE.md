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

No starter kit (Breeze/Jetstream) is installed. Organization and
everything past RBAC is unbuilt — see Status below.

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

**Not started:** Phase 5 (Organization) onward, through Phase 18. Follow
the phase order in blueprint §54/§59; don't jump ahead to a later phase's
tables/UI before its dependencies exist. Re-read the relevant blueprint
section before starting a phase — this file is a summary, not a
substitute.

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
(Organization) and Phase 6 (Employee) don't exist yet to scope against.
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
