#!/usr/bin/env bash
# scripts/composer.sh
# Convenience setup script for the project.
# Usage:
#   ./scripts/composer.sh            # default: ensure composer, install deps, dump-autoload, ensure mpdf, make upload dirs
#   ./scripts/composer.sh --no-mpdf  # skip composer require mpdf (if you already have it)
#   ./scripts/composer.sh --init-db  # <-- OPTIONAL: run php scripts/setup_db.php (CLI only)
#   ./scripts/composer.sh --seed     # <-- OPTIONAL: run php scripts/seed_demo_data.php [-v]
#   ./scripts/composer.sh --full     # do everything including --init-db and --seed (careful)
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

VERBOSE=false
DO_INSTALL_DB=false
DO_SEED=false
DO_REQUIRE_MPDF=true
COMPOSER_BIN="composer"

print() { printf "%s\n" "$*"; }
err() { printf "ERROR: %s\n" "$*" >&2; exit 1; }

# parse arguments
for arg in "$@"; do
  case "$arg" in
    -v|--verbose) VERBOSE=true ;;
    --no-mpdf) DO_REQUIRE_MPDF=false ;;
    --init-db) DO_INSTALL_DB=true ;;
    --seed) DO_SEED=true ;;
    --full) DO_INSTALL_DB=true; DO_SEED=true ;;
    *) ;;
  esac
done

# colorless logging (respect VERBOSE)
log() {
  if [ "$VERBOSE" = true ]; then
    print "[composer.sh] $*"
  fi
}

# 1) ensure php exists
if ! command -v php >/dev/null 2>&1; then
  err "PHP CLI is required but not found in PATH. Install PHP (php-cli) and try again."
fi
log "PHP CLI found: $(php -v | head -n1)"

# 2) ensure composer; if not present try to download locally
if command -v composer >/dev/null 2>&1; then
  COMPOSER_BIN="composer"
  log "Global composer found: $(composer --version 2>/dev/null || true)"
else
  # try project-local composer.phar
  if [ -f "$ROOT/composer.phar" ]; then
    COMPOSER_BIN="php composer.phar"
    log "Using project composer.phar"
  else
    print "Composer not found globally. Attempting to download composer.phar to project root..."
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" || err "Failed to download composer installer."
    HASH_EXPECTED=$(curl -sS https://composer.github.io/installer.sig)
    HASH_ACTUAL=$(php -r "echo hash_file('sha384', 'composer-setup.php');")
    if [ "$HASH_EXPECTED" != "$HASH_ACTUAL" ]; then
      print "WARNING: composer installer checksum mismatch — continuing anyway."
    fi
    php composer-setup.php --quiet || err "Composer installer failed"
    rm -f composer-setup.php
    if [ -f composer.phar ]; then
      COMPOSER_BIN="php composer.phar"
      log "Downloaded composer.phar"
    else
      err "Failed to install composer.phar"
    fi
  fi
fi

# 3) composer install (reads composer.json)
print "Running: $COMPOSER_BIN install --no-interaction --no-progress"
$COMPOSER_BIN install --no-interaction --no-progress

# 4) ensure mpdf is present in composer.json / vendor
if [ "$DO_REQUIRE_MPDF" = true ]; then
  if grep -qi '"mpdf/mpdf"' composer.json >/dev/null 2>&1 || vendor/mpdf >/dev/null 2>&1; then
    log "mpdf already required in composer.json or present in vendor/"
  else
    print "Requiring mpdf/mpdf via composer..."
    $COMPOSER_BIN require mpdf/mpdf --no-interaction --no-progress
  fi
else
  log "Skipping mpdf requirement as requested."
fi

# 5) regenerate autoload
print "Running: $COMPOSER_BIN dump-autoload --optimize --no-interaction"
$COMPOSER_BIN dump-autoload --optimize --no-interaction

# 6) create upload dirs and set permissions
UPLOAD_DIRS=(public/uploads public/uploads/pdfs)
for d in "${UPLOAD_DIRS[@]}"; do
  if [ ! -d "$ROOT/$d" ]; then
    mkdir -p "$ROOT/$d"
    log "Created $d"
  fi
done

# set safe perms for webserver (non-recursive for safety)
if id -u www-data >/dev/null 2>&1; then
  WEB_USER="www-data"
elif id -u www >/dev/null 2>&1; then
  WEB_USER="www"
else
  WEB_USER=""
fi

if [ -n "$WEB_USER" ]; then
  print "Setting ownership of uploads to $WEB_USER (requires sudo)..."
  if command -v sudo >/dev/null 2>&1; then
    sudo chown -R "$WEB_USER":"$WEB_USER" "${UPLOAD_DIRS[@]/#/$ROOT/}"
  else
    print "  (sudo not available — skipping chown)"
  fi
else
  log "Web user not detected; skipping chown."
fi

# make writable by owner and group; keep it conservative
chmod -R u+rwX,g+rwX "$ROOT/public/uploads" || true
log "Set upload dir permissions."

# 7) Optional DB / seed steps
if [ "$DO_INSTALL_DB" = true ]; then
  if [ -f "$ROOT/scripts/setup_db.php" ]; then
    print "Initializing DB (scripts/setup_db.php)..."
    php "$ROOT/scripts/setup_db.php" || err "setup_db.php failed"
  else
    print "No scripts/setup_db.php found — skipping DB init."
  fi
fi

if [ "$DO_SEED" = true ]; then
  if [ -f "$ROOT/scripts/seed_demo_data.php" ]; then
    print "Seeding demo data (scripts/seed_demo_data.php)..."
    php "$ROOT/scripts/seed_demo_data.php" "${VERBOSE:+-v}" || err "seed_demo_data.php failed"
  else
    print "No scripts/seed_demo_data.php found — skipping seed."
  fi
fi

print "Done. Composer & project setup complete."
