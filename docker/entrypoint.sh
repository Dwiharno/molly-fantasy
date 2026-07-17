#!/usr/bin/env sh
set -eu

if [ -z "${APP_KEY:-}" ] && [ -n "${APP_KEY_SECRET:-}" ]; then
    export APP_KEY="base64:${APP_KEY_SECRET}"
fi

if [ -z "${APP_KEY:-}" ]; then
    echo "APP_KEY atau APP_KEY_SECRET wajib tersedia." >&2
    exit 1
fi

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
