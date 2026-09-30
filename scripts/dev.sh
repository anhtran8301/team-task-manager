#!/bin/sh
set -eu
cd "$(dirname "$0")/.."
php be/artisan serve --host=127.0.0.1 --port=8000 &
backend_pid=$!
trap 'kill "$backend_pid" 2>/dev/null || true' EXIT INT TERM
npm --prefix fe run dev -- --host 127.0.0.1
