#!/usr/bin/env bash
set -euo pipefail

if [[ -z "${APP_KEY:-}" && -n "${APP_KEY_SECRET:-}" ]]; then
  export APP_KEY="base64:${APP_KEY_SECRET}"
fi

if [[ -z "${APP_KEY:-}" ]]; then
  echo "APP_KEY wajib tersedia pada Replit Secrets." >&2
  exit 1
fi

mkdir -p \
  storage/app/private \
  storage/app/public \
  storage/framework/cache \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs \
  bootstrap/cache

php artisan storage:link --force || true
rm -f bootstrap/cache/config.php bootstrap/cache/routes-*.php bootstrap/cache/events.php
php artisan migrate --force

if [[ "${RUN_SEEDER:-true}" == "true" ]]; then
  php artisan db:seed --force
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
