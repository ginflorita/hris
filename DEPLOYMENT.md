# Deployment & Disaster Recovery

Blueprint §54 Phase 18 ("Production, Backup & Disaster Recovery"). This
is the procedural half of that phase — the config files it references
live in `deploy/`, the backup/restore commands in
`app/Console/Commands/`, and CI already runs on every push to `main`
and every pull request (`.github/workflows/tests.yml`, in place since
this project's first commit — tests across PHP 8.3/8.4 plus a Pint
lint check). See CLAUDE.md's "Production, Backup & Disaster Recovery"
section for the reasoning behind each piece; this file is the
step-by-step a deployer actually follows.

## Prerequisites

- PHP 8.3+ with the extensions in `.github/workflows/tests.yml`'s CI
  matrix (dom, curl, libxml, mbstring, zip, pcntl, pdo, pdo_mysql)
- MySQL 8+ (CLAUDE.md's Stack — SQLite is a local/sandbox-only fallback,
  never used in production)
- Redis (session/cache/queue backing in production, per
  `.env.production.example`)
- Nginx + PHP-FPM (`deploy/nginx/hris.conf`, `deploy/php-fpm/hris.conf`)
- Supervisor, for the queue worker (`deploy/supervisor/hris-worker.conf`)
- Composer 2, Node 20+

## Initial deployment

1. Clone the repository to `/var/www/hris` under a dedicated, unprivileged
   `hris` system user (blueprint §51 17.21's least-privilege principle
   applies to the OS user running the app, not just the database user).
2. `composer install --no-dev --optimize-autoloader`
3. `npm ci && npm run build`
4. `cp .env.production.example .env`, then fill in every blank —
   `APP_KEY` (`php artisan key:generate`), database credentials, Redis,
   mail transport. Never commit the filled-in file.
5. `php artisan migrate --force`
6. `php artisan storage:link` (public disk only — `storage/app/private/`
   is deliberately never web-accessible, per CLAUDE.md's Employee
   section; nothing in this app should ever symlink it)
7. `php artisan config:cache && php artisan route:cache && php artisan view:cache`
8. Install `deploy/nginx/hris.conf` and `deploy/php-fpm/hris.conf` (adjust
   `server_name`, TLS certificate paths, and the PHP version path to
   match the target), then `nginx -t && systemctl reload nginx` and
   restart PHP-FPM.
9. Install `deploy/supervisor/hris-worker.conf`, then
   `supervisorctl reread && supervisorctl update && supervisorctl start hris-worker:*`
   — without this the queue notifications Phase 18b made real
   (`SecurityAlert`, `PayslipPublished`, `TrainingCertificateExpiring`)
   will sit in the `jobs` table indefinitely.
10. Add the scheduler's single cron entry as the `hris` user
    (`crontab -e`):
    ```
    * * * * * cd /var/www/hris && php artisan schedule:run >> /dev/null 2>&1
    ```
    This one line is what actually fires every command Phase 18a
    registered in `routes/console.php` (`leave:accrue`,
    `leave:carry-over`, `training:send-certificate-expiration-reminders`)
    plus 18c's `backup:run`/`backup:verify-latest` — none of them run on
    their own without it.
11. Seed the initial Superadmin account — `php artisan db:seed
    --class=DatabaseSeeder` **only** on a genuinely first deployment
    (CLAUDE.md's Authentication section: "never runs in production
    automatically" is about not re-running it, not about never running
    it once).

## Deploying an update

1. `git fetch && git checkout <tag or commit>`
2. `composer install --no-dev --optimize-autoloader`
3. `npm ci && npm run build`
4. `php artisan down --secret="<one-time-bypass-token>"` if the release
   includes a migration that isn't purely additive (most of this app's
   own migrations are additive-only per CLAUDE.md's non-negotiable
   rules, so this is the exception, not the default)
5. `php artisan migrate --force`
6. `php artisan config:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache`
7. `supervisorctl restart hris-worker:*` (queue workers hold the old
   code in memory until restarted)
8. `php artisan up`

## Rollback

Prefer **forward-fixing** over rolling back — CLAUDE.md's own
non-negotiable rules (never overwrite historical data, payroll immutable
once finalized) mean most of this app's migrations only ever add
columns/tables, so `migrate:rollback` against a release that already
processed real payroll, leave, or attendance data risks dropping columns
live rows still depend on. If a genuine rollback is unavoidable:

1. `php artisan down`
2. Confirm the target commit's migrations are safe to reverse — read
   each migration's own `down()` method for the range being rolled back,
   don't assume
3. `php artisan migrate:rollback --step=<n> --force` only if step 2
   checked out
4. `git checkout <previous tag>`, `composer install --no-dev`, `npm ci && npm run build`
5. `php artisan config:cache && supervisorctl restart hris-worker:* && php artisan up`

## Disaster recovery

Phase 18c's `backup:run`/`backup:restore`/`backup:verify-latest`
deliberately stop short of touching the live database or
`storage/app/private/` themselves (see CLAUDE.md — that needs the app
stopped first, a procedure decision, not something an automated command
should do silently). This is that procedure:

1. `php artisan down` — do not restore into a live, in-use SQLite file
   or a MySQL database still accepting writes.
2. Locate the backup to restore (`storage/app/backups/<timestamp>/`,
   or wherever off-host backups are shipped to — this app doesn't
   manage off-host replication itself, see "Not built" below).
3. `php artisan backup:restore storage/app/backups/<timestamp> /tmp/hris-restore`
   — decrypts and checksum-verifies into a scratch location first, so a
   corrupted or tampered backup is caught *before* anything live is
   touched.
4. Once verified: for MySQL, load `/tmp/hris-restore/database.sql` into
   the live database (`mysql hris < /tmp/hris-restore/database.sql`,
   after confirming the target schema is empty or intentionally being
   overwritten); for the SQLite local-dev fallback, copy
   `/tmp/hris-restore/database.sqlite` over the configured
   `DB_DATABASE` path directly.
5. Sync `/tmp/hris-restore/files/` into `storage/app/private/` (e.g.
   `rsync -a --delete`).
6. Restore `/tmp/hris-restore/.env` only if the live `.env` was itself
   lost — otherwise leave the current, already-correct one in place.
7. `php artisan up`, then manually verify: log in, open an employee
   record, download a payslip — the same golden-path check every phase
   in this project has run via Playwright before considering a change
   shipped.
8. Clean up `/tmp/hris-restore`.

**Not built**: off-host backup shipping/replication (the encrypted
backups `backup:run` produces stay on the same host under
`storage/app/backups/` unless something else — rsync to a second host,
an S3 sync, etc. — moves them off it; a real deployment needs that step
for the backup to survive the primary host itself failing, which this
codebase can't provide without knowing the target's actual off-host
storage). Documented here as the one genuine gap in this slice, not
silently assumed solved.

## Monitoring & error monitoring

What this app already provides natively, with no external service
required: `login_logs` (Phase 3), `payslip_access_logs` (Phase 12c),
`audit_logs` (Phase 17b), and `SecurityAlert`/`PayslipPublished`/
`TrainingCertificateExpiring` email notifications. None of these
substitute for real infrastructure monitoring (uptime, error-rate
alerting, APM) — that needs an external service (Sentry, Bugsnag, a
hosted log aggregator) with its own account and credentials this
codebase has no way to provision for a specific deployer. Laravel's
`config/logging.php` already supports adding a monitoring channel
(e.g. a Sentry DSN) with no application code changes once a deployer
has one — this is a configuration step for whoever deploys, not a gap
in the code.

## CI/CD

Already in place (`.github/workflows/tests.yml`, present since this
project's first commit, not built in this phase): a `tests` job running
the full suite on PHP 8.3 and 8.4 against SQLite, and a `lint` job
running `vendor/bin/pint --test`, both on every push to `main` and every
pull request.
