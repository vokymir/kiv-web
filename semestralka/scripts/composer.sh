#!/usr/bin/env bash
# scripts/composer.sh
# Minimal Composer setup helper
# Usage:
#   ./scripts/composer.sh              # uses system php
#   ./scripts/composer.sh --xampp      # use XAMPP php (/opt/lampp/bin/php)
#   ./scripts/composer.sh --php=/path/to/php  # custom php binary

set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

PHP_BIN="php"
for arg in "$@"; do
  case "$arg" in
    --xampp) PHP_BIN="/opt/lampp/bin/php" ;;
    --php=*) PHP_BIN="${arg#*=}" ;;
  esac
done

if ! command -v "$PHP_BIN" >/dev/null 2>&1; then
  echo "ERROR: PHP binary not found: $PHP_BIN" >&2
  exit 1
fi

COMPOSER_PHAR="$ROOT/composer.phar"
if [ ! -f "$COMPOSER_PHAR" ]; then
  echo "Downloading composer.phar..."
  curl -sS https://getcomposer.org/installer -o composer-setup.php
  "$PHP_BIN" composer-setup.php --quiet
  rm -f composer-setup.php
fi

echo "Running composer install..."
"$PHP_BIN" "$COMPOSER_PHAR" install --no-interaction --no-progress --prefer-dist

echo "Dumping autoload..."
"$PHP_BIN" "$COMPOSER_PHAR" dump-autoload --optimize

echo "✅ Composer setup complete."
