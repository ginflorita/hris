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

## Project status

Phase 1 (project foundation), part of Phase 2 (UI shell), Phase 3
(authentication — login, 2FA, password reset, sessions), and Phase 4
(RBAC — roles, permissions, Superadmin protection, user management) are
in place — see the dashboard's build-status panel or `CLAUDE.md` for
what's done and what's next. The system is built in phases per
[`docs/HRIS_Blueprint.md` §54](docs/HRIS_Blueprint.md#54-development-phases);
modules are not built out of order.

## License

MIT — see [`LICENSE`](LICENSE).
