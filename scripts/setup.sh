#!/bin/sh
set -eu
cd "$(dirname "$0")/.."
php -r 'exit(PHP_MAJOR_VERSION === 8 && PHP_MINOR_VERSION === 3 ? 0 : 1);' || { echo 'PHP 8.3 is required on PATH.' >&2; exit 1; }
npm ci
npm --prefix fe ci
[ -f be/.env ] || cp be/.env.example be/.env
composer --working-dir=be install
if ! grep -q '^APP_KEY=base64:' be/.env; then php be/artisan key:generate; fi
php be/artisan migrate --force
php be/artisan db:seed --force
