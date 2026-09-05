# HRIS

A modular Human Resources Information System — employee records, attendance,
leave, payroll, recruitment, performance, training, and benefits, built as
one Laravel application with admin, manager, and employee portals.

The full functional and security specification lives in
[`docs/HRIS_Blueprint.md`](docs/HRIS_Blueprint.md). [`CLAUDE.md`](CLAUDE.md)
summarizes architecture conventions and current build status.

## Stack

Laravel 12 (PHP 8.3+) · MySQL 8+ · Blade · Bootstrap 5.3+ · Vite · Alpine.js ·
Redis (cache/queue)

## Requirements

- PHP 8.3+ with the usual Laravel extensions (`pdo`, `mbstring`, `openssl`, …)
- Composer 2
- Node.js 20+ and npm
- MySQL 8+ and Redis for a production-like setup — for local development
  without either, the app falls back to SQLite and database-backed
  cache/queue (see `.env.example`)

## Setup

```bash
composer install
npm install

cp .env.example .env
# Edit .env for your MySQL/Redis connection, or leave DB_CONNECTION=sqlite
# for zero-config local development.
php artisan key:generate

php artisan migrate
php artisan db:seed   # optional: creates one dev login, see database/seeders/DatabaseSeeder.php

npm run build   # or `npm run dev` for hot reload while working on frontend
php artisan serve
```

## Testing & code style

```bash
composer test        # PHPUnit
vendor/bin/pint       # format code
vendor/bin/pint --test  # check formatting without changing files
```

## Deployment

See [`DEPLOYMENT.md`](DEPLOYMENT.md) for the full walkthrough,
including disaster recovery. Two supported paths, pick whichever
matches the target host — a bare server, or anywhere that runs
containers:

- **Bare server** (Nginx + PHP-FPM + Supervisor): configs in
  [`deploy/`](deploy/); `.github/workflows/deploy.yml` can run an
  update over SSH from a manual GitHub Actions trigger.
- **Container**: the root [`Dockerfile`](Dockerfile) builds one image
  (web/worker/scheduler roles, selected by command);
  `.github/workflows/docker-publish.yml` builds and pushes it to GHCR
  automatically. [`docker-compose.yml`](docker-compose.yml) runs the
  same image against real MySQL + Redis containers for local testing —
  copy [`.env.docker-compose.example`](.env.docker-compose.example) to
  `.env.docker-compose` first.

MySQL 8+ and Redis are always separate services in either path, never
bundled into the app itself.

## Project status

All of blueprint [§54](docs/HRIS_Blueprint.md#54-development-phases)'s
18 phases are complete, plus two later additions this project's own
history documents in full (`CLAUDE.md`) but blueprint never scheduled:

| Phases | Covers |
|---|---|
| 1–2 | Project foundation, admin UI shell |
| 3–4 | Authentication (2FA, sessions) and RBAC |
| 5–7 | Organization hierarchy, Employee core HR, Employment history |
| 8–10 | Attendance & scheduling, Leave management, Compensation |
| 11–12 | Payroll engine through approval, finalization, and digital payslips |
| 13 | Employee & Manager Self-Service portal |
| 14–16 | Recruitment & Onboarding, Talent Management, Benefits & Offboarding |
| 17–18 | Security Hardening & OWASP verification, Production/Backup/DR |
| 19 | Reports & Analytics (named in blueprint §3, never assigned a phase) |
| 20 | Workflow Engine (named in blueprint §27, never assigned a phase) |

Every deliberate, documented gap along the way (things intentionally
*not* built, and why) is named in its own section of `CLAUDE.md` — that
file, not this one, is the authoritative accounting of what's done
versus what's a real, sized follow-up.

## License

MIT — see [`LICENSE`](LICENSE).
