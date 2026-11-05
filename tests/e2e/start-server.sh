#!/usr/bin/env bash
set -euo pipefail

export APP_ENV=testing
export APP_URL=http://127.0.0.1:8011
export APP_CONFIG_CACHE="/private/tmp/sanitra-e2e-config-${$}.php"
export DB_CONNECTION=sqlite
export DB_DATABASE="$(mktemp /private/tmp/sanitra-e2e-XXXXXX)"
export SESSION_DRIVER=file
export CACHE_STORE=file
export QUEUE_CONNECTION=sync
export MAIL_MAILER=array

php -r 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); if ($app->environment() !== "testing" || config("database.default") !== "sqlite" || config("database.connections.sqlite.database") !== getenv("DB_DATABASE")) { fwrite(STDERR, "E2E database isolation check failed.\n"); exit(1); }'
php artisan migrate --force --no-interaction
php artisan db:seed --class=SanitraMasterSeeder --force --no-interaction
php artisan serve --host=127.0.0.1 --port=8011 --no-interaction
