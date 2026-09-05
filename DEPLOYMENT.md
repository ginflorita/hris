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

## Container deployment (Docker)

Added after Phase 18 closed, in response to a direct question about
deploying via GitHub -- not a blueprint-driven phase slice, so it's
kept clearly separate from the numbered-phase sections above rather
than folded into Phase 18d's own history. An alternative to the
bare-VPS steps above, not a replacement for them: pick whichever
matches the target host. `Dockerfile` (repo root) packages the exact
same PHP 8.3 + Nginx + PHP-FPM stack `deploy/` already documents for a
bare server, as one image, for platforms that run containers instead
(Fly.io, Render, ECS, a self-managed Docker host, etc.).

**MySQL 8+ and Redis are still separate services, never bundled into
this image** -- a managed database/cache add-on, or sibling containers
of their own. Nothing about containerizing changes what CLAUDE.md's
Stack section already requires in production.

**One image, three roles, selected by the container's command**
(`docker/entrypoint.sh` dispatches on it):

```
docker run --env-file .env.production -p 8080:8080 ghcr.io/<owner>/hris          # web (Nginx + PHP-FPM); default if omitted
docker run --env-file .env.production                ghcr.io/<owner>/hris worker  # queue:work -- Phase 18b's ShouldQueue notifications need this running somewhere
docker run --env-file .env.production                ghcr.io/<owner>/hris schedule # runs whatever's due once, then exits
```

Run `web` continuously; run `worker` continuously as a second
service/process (same image, different command -- most platforms with
a "background worker" service type point it at exactly this); point
your platform's own scheduled-job/cron feature at `schedule` on a
one-minute interval, the containerized equivalent of this file's
bare-VPS crontab line above. There is no cron daemon baked into the
image on purpose -- a platform-native scheduled job is more visible and
more portable than a busy-loop process pretending to be cron.

**Migrations are opt-in, not automatic**, via `RUN_MIGRATIONS=true` as
an env var on a one-off run of the `web` role, or your platform's own
"release command"/"pre-deploy command" feature if it has one
(`docker run --env-file .env.production -e RUN_MIGRATIONS=true ghcr.io/<owner>/hris web`,
then stop it once it's up, or use a role/command your platform treats
as a one-shot task instead of a long-running service). Running it
unconditionally on every container start would race every replica of
the `web` role against each other the moment you scale past one.

**Config is cached at container *start*, not baked into the image at
build time** -- `entrypoint.sh` runs `config:cache`/`route:cache`/
`view:cache` itself, after the platform's real environment variables
are already injected, specifically so the cached config reflects the
actual secrets for this deployment rather than freezing in whatever
was present (or absent) during the image build.

**Built and published to GHCR by `.github/workflows/docker-publish.yml`**
on every push to `main`, every version tag, or manually -- see
"Automated deployment via GitHub Actions" below. No secrets need
configuring for that workflow specifically; it uses the repository's
own built-in `GITHUB_TOKEN`.

**Not build-verified in the session that wrote it** -- see the
Dockerfile's own top comment for exactly why (this sandbox's egress
policy blocks Docker Hub itself, confirmed via the proxy's own status
endpoint, not a fixable local issue) and what was checked instead
(the Dockerfile parses and all three build stages resolve correctly).
The real build-and-run test happens the first time
`docker-publish.yml` runs on GitHub's own infrastructure, which has no
such restriction -- watch that run before trusting the image in
production.

**Testing the real MySQL + Redis path locally, before trusting it
anywhere real**: `docker-compose.yml` (repo root) runs the image above
plus MySQL and Redis containers of their own -- copy
`.env.docker-compose.example` to `.env.docker-compose` (deliberately
not `.env`; see that file's own header for why that name specifically
would collide with this repo's real local-dev file), generate a real
`APP_KEY` (`docker compose run --rm app php artisan key:generate
--show`, paste it in), then `docker compose up --build`. This is not a
replacement for the SQLite-fallback local dev CLAUDE.md's Stack
section already documents (`php artisan serve` + `npm run dev`, no
Docker needed) -- it exists specifically to exercise the MySQL/Redis
path that fallback never touches. Structurally validated with `docker
compose config` (no image pull needed for that) rather than a real
`up`, for the same Docker Hub reason as the image itself -- and that
validation caught a real bug on the first pass: an earlier version of
this file named the target `.env`, and `docker compose config`'s
resolved output came back holding this *repo's own real local-dev
values* (SQLite, 127.0.0.1) instead of anything from
`.env.docker-compose.example`, because Compose's `env_file:` directive
resolves strictly from the literal filename given, unrelated to the
`--env-file` flag used to check it -- it had silently found and loaded
this project's actual `.env` instead. Renaming the target removed the
collision outright; re-running the same `config` check afterward
confirmed the fix (`DB_HOST: mysql`, `REDIS_HOST: redis`, matching the
compose file's own service names, not the old values).

## Automated deployment via GitHub Actions

Two workflows, both added alongside the Dockerfile above and both
inert until configured -- see "CI/CD" below for the one that already
existed before this session.

**`.github/workflows/docker-publish.yml`** builds the Dockerfile above
and pushes it to `ghcr.io/<owner>/<repo>` on every push to `main`,
every `v*` tag, or manually via "Run workflow." Needs no secrets of
its own (GHCR auth uses the run's own `GITHUB_TOKEN`); the repository
does need "Read and write permissions" enabled for `GITHUB_TOKEN` under
Settings -> Actions -> General -> Workflow permissions, since a fresh
repository sometimes defaults that to read-only.

**`.github/workflows/deploy.yml`** runs this file's own "Deploying an
update" steps over SSH against an existing bare-VPS install --
manual-dispatch only, so it does nothing until both triggered by hand
and its secrets exist. Requires, as repository secrets (Settings ->
Secrets and variables -> Actions):

- `DEPLOY_HOST` -- the server's hostname or IP
- `DEPLOY_USER` -- the unprivileged `hris` deploy user from "Initial
  deployment" step 1 above
- `DEPLOY_SSH_KEY` -- a private key for that user, authorized
  (`~/.ssh/authorized_keys`) on the target host

Optionally `DEPLOY_PORT` (default 22) and `DEPLOY_PATH` (default
`/var/www/hris`).

**One prerequisite this workflow needs that the manual walkthrough
above doesn't**: the manual steps assume a human with their own sudo
session runs `supervisorctl`; this workflow runs as the unprivileged
`hris` deploy user with no interactive terminal to type a sudo password
into, so restarting the queue workers after a deploy needs one of:

- a narrow, specific sudoers rule (`/etc/sudoers.d/hris-deploy`):
  `hris ALL=(root) NOPASSWD: /usr/bin/supervisorctl restart hris-worker:*`
  -- least-privilege, matching blueprint §51 17.21, since it grants
  exactly one command and nothing else; or
- adding `hris` to a group Supervisor's own control socket already
  grants read/write access to, so `supervisorctl` needs no elevation
  at all.

Grant whichever the target host's own conventions prefer before the
first automated run -- without it, the deploy still updates the code
and database but the restart step fails, leaving the previous
release's code running in the queue workers' memory until manually
restarted.

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

Already in place since Phase 18d (`.github/workflows/tests.yml`,
present since this project's first commit, not built in that phase
either): a `tests` job running the full suite on PHP 8.3 and 8.4
against SQLite, and a `lint` job running `vendor/bin/pint --test`, both
on every push to `main` and every pull request.

`docker-publish.yml` and `deploy.yml` (see "Container deployment" and
"Automated deployment via GitHub Actions" above) were added later,
outside Phase 18's own scope, in direct response to a question about
deploying this app via GitHub -- `tests.yml` verifies a change is
correct; these two are about actually shipping one.
