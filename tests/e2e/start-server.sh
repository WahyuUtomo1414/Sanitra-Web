#!/usr/bin/env bash
set -euo pipefail

# Defense-in-depth: a stale default bootstrap/cache/config.php (e.g. left
# over from `php artisan config:cache` run outside this script) is exactly
# the scenario docs/testing-safety.md warns about — it can make Laravel
# serve this E2E run on the real local MySQL instead of the isolated SQLite
# DB below. APP_CONFIG_CACHE is overridden per-run so this script's own
# `php artisan` calls never read that stale file, but clear it anyway so
# nothing else sharing this checkout can pick it up accidentally.
rm -f bootstrap/cache/config.php

export APP_ENV=testing
export APP_URL=http://127.0.0.1:8011
# .env.testing (shared with PHPUnit) doesn't set APP_LOCALE, so without this
# the app falls back to config/app.php's default 'en' — rendering "Email
# address"/"Password" instead of the real app's "Alamat email"/"Kata sandi"
# that every E2E selector expects.
export APP_LOCALE=id
export APP_CONFIG_CACHE="/private/tmp/sanitra-e2e-config-${$}.php"
export DB_CONNECTION=sqlite
export DB_DATABASE="$(mktemp /private/tmp/sanitra-e2e-XXXXXX)"
export SESSION_DRIVER=file
# array (not file): file-backed cache writes to storage/framework/cache,
# shared with the real local app — login rate-limiter hits there pile up
# across runs and accumulate towards the 5-attempts/60s cap for no reason.
# array keeps it in-process memory, truly isolated per run.
export CACHE_STORE=array
export QUEUE_CONNECTION=sync
export MAIL_MAILER=array

php -r 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); if ($app->environment() !== "testing" || config("database.default") !== "sqlite" || config("database.connections.sqlite.database") !== getenv("DB_DATABASE")) { fwrite(STDERR, "E2E database isolation check failed.\n"); exit(1); }'
php artisan migrate --force --no-interaction
# RoleSeeder (called by SanitraMasterSeeder) only syncs super_admin to
# permission rows that already exist in the DB — it does not create every
# possible Create/Update/Delete permission itself (only what Marketing/
# Finance/Teknis explicitly need). On the real app those rows exist because
# `shield:generate` was run historically; a fresh isolated DB has none of
# them, so super_admin silently ends up missing Create/Edit/Delete access on
# resources RoleSeeder doesn't mention (e.g. MsBankAccount, MsUnit). Generate
# them first so super_admin's "sync all web permissions" picks up everything.
php artisan shield:generate --all --option=permissions --panel=dashboard --no-interaction
php artisan db:seed --class=SanitraMasterSeeder --force --no-interaction
# --no-reload is required: without it, Laravel's ServeCommand only forwards
# a hardcoded allowlist of env vars (APP_ENV, PATH, …) to the actual request
# -handling subprocess and silently drops everything we export above
# (DB_CONNECTION, DB_DATABASE, APP_LOCALE, …). Without --no-reload the
# server falls back to .env.testing's DB_DATABASE=:memory: — an empty,
# never-migrated database — not the seeded temp SQLite file created below.
php artisan serve --host=127.0.0.1 --port=8011 --no-interaction --no-reload
