#!/usr/bin/env bash
# setup.sh — project setup orchestrator
# Usage:
#   ./scripts/setup.sh [options]
#
# Options:
#   -v, --verbose     Enable verbose output
#   --xampp           Use XAMPP PHP binary (/opt/lampp/bin/php)
#   --php=/path/php   Use custom PHP binary
#   --no-composer     Skip composer install
#
# Runs:
#   scripts/composer.sh
#   scripts/drop_db.php
#   scripts/setup_db.php
#   scripts/seed_users.php
#   scripts/seed_posts.php
#   scripts/seed_reviews.php

set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT"

# --- Defaults ---
VERBOSE=false
PHP_BIN="php"
DO_COMPOSER=true

# --- Parse arguments ---
for arg in "$@"; do
  case "$arg" in
    -v|--verbose) VERBOSE=true ;;
    --xampp) PHP_BIN="/opt/lampp/bin/php" ;;
    --php=*) PHP_BIN="${arg#*=}" ;;
    --no-composer) DO_COMPOSER=false ;;
    *) echo "Unknown option: $arg" >&2; exit 1 ;;
  esac
done

log() { [ "$VERBOSE" = true ] && echo "[setup.sh] $*"; }

# --- Check PHP ---
if ! command -v "$PHP_BIN" >/dev/null 2>&1; then
  echo "ERROR: PHP not found: $PHP_BIN" >&2
  exit 1
fi
log "Using PHP: $($PHP_BIN -v | head -n1)"

# --- Composer setup ---
if [ "$DO_COMPOSER" = true ]; then
  log "Running composer setup..."
  "$ROOT/composer.sh" --php="$PHP_BIN"
else
  log "Skipping composer setup (--no-composer)"
fi

# --- Database reset + seed ---
log "Dropping old database..."
$PHP_BIN "$ROOT/drop_db.php" || true

log "Creating database schema..."
$PHP_BIN "$ROOT/setup_db.php"

log "Seeding users..."
$PHP_BIN "$ROOT/seed_users.php"

log "Seeding posts..."
$PHP_BIN "$ROOT/seed_posts.php" --pdf

log "Seeding reviews..."
$PHP_BIN "$ROOT/seed_reviews.php"

mkdir -p ../public/uploads && chmod 0777 ../public/uploads

echo "✅ Setup complete!"
