#!/bin/sh
set -eu
cd "$(dirname "$0")/.."
npm run format:check
composer --working-dir=be validate --strict
composer --working-dir=be lint
composer --working-dir=be test
npm --prefix fe run lint
npm --prefix fe run typecheck
npm --prefix fe test
npm --prefix fe run build
