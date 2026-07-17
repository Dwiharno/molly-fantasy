#!/usr/bin/env bash
set -euo pipefail

composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
npm ci --ignore-scripts
npm run copy-assets

mkdir -p \
  storage/app/private \
  storage/app/public \
  storage/framework/cache \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs \
  bootstrap/cache

php artisan storage:link --force || true
