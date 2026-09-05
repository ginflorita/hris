#!/bin/sh
set -e

# This image has no baked-in .env -- every setting comes from real
# process environment variables the platform injects at container
# start (a Docker/PaaS convention, not a departure from
# .env.production.example: that file still documents every key a
# deployer needs to set, just as platform env vars instead of a
# committed file). Config is cached HERE, at container start, rather
# than at image build time, specifically because `config:cache` bakes
# whatever env() returns at the moment it runs -- caching it during the
# image build would freeze in build-time defaults instead of the real
# secrets the platform provides at run time.
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Opt-in, not automatic -- see docker/README section of DEPLOYMENT.md
# for why. Running migrate on every container start would race N
# replicas of the same image against each other; a platform's own
# one-off "release/pre-deploy command" feature (or a manual
# `docker run ... migrate`) is the correct place for this, same
# principle DEPLOYMENT.md's own bare-VPS steps already separate
# "deploy code" from "run migrate" into distinct steps.
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
fi

role="${1:-web}"

case "$role" in
    web)
        exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
        ;;
    worker)
        # Matches deploy/supervisor/hris-worker.conf's own command --
        # the queue-worker *process* Phase 18b's ShouldQueue
        # notifications need, containerized instead of Supervisor-on-a-
        # VPS. Run this role as a second service/process alongside
        # `web`, not instead of it.
        exec php artisan queue:work --sleep=3 --tries=3 --max-time=3600
        ;;
    schedule)
        # Fires whatever's due right now and exits -- point your
        # platform's own scheduled-job/cron feature at
        # `docker run <image> schedule` on a one-minute interval,
        # the containerized equivalent of DEPLOYMENT.md's crontab line.
        exec php artisan schedule:run
        ;;
    *)
        # Anything else is passed straight through, so this image still
        # works as a normal `php artisan ...`/one-off-command runner
        # (e.g. `docker run <image> php artisan migrate --force`).
        exec "$@"
        ;;
esac
