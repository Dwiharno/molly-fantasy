#!/usr/bin/env sh
set -eu

mkdir -p storage/app/private storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

if [ -n "${GOOGLE_SHEETS_CREDENTIALS_BASE64:-}" ]; then
    mkdir -p storage/app/google
    printf '%s' "$GOOGLE_SHEETS_CREDENTIALS_BASE64" | base64 -d > storage/app/google/service-account.json
fi

php artisan storage:link --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force

if [ "${RUN_SEEDER:-true}" = "true" ]; then
    php artisan db:seed --force
fi

exec php artisan serve --host=0.0.0.0 --port="${PORT:-10000}"
