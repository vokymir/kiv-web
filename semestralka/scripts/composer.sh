#!/usr/bin/env bash
# scripts/composer.sh
# Convenience setup script for the project.
# Usage:
#   ./scripts/composer.sh            # default: ensure composer/phar via chosen PHP, install deps, dump-autoload, make upload dirs
#   ./scripts/composer.sh --no-mpdf  # skip composer require mpdf (if you already have it)
#   ./scripts/composer.sh --init-db  # run php scripts/setup_db.php (CLI only)
#   ./scripts/composer.sh --seed     # run php scripts/seed_demo_data.php [-v]
#   ./scripts/composer.sh --full     # do everything including --init-db and --seed
#   ./scripts/composer.sh --xampp-php # use XAMPP php binary (/opt/lampp/bin/php)
#   ./scripts/composer.sh --php-bin=/path/to/php  # use custom php binary
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

# Defaults
VERBOSE=false
DO_INSTALL_DB=false
DO_SEED=false
DO_REQUIRE_MPDF=true
PHP_BIN="php"                  # default PHP binary (can be overridden by --xampp-php or --php-bin)
COMPOSER_PHAR="$ROOT/composer.phar"

print() { printf "%s\n" "$*"; }
err()  { printf "ERROR: %s\n" "$*" >&2; exit 1; }

# parse arguments
for arg in "$@"; do
  case "$arg" in
    -v|--verbose) VERBOSE=true ;;
    --no-mpdf) DO_REQUIRE_MPDF=false ;;
    --init-db) DO_INSTALL_DB=true ;;
    --seed) DO_SEED=true ;;
    --full) DO_INSTALL_DB=true; DO_SEED=true ;;
    --xampp-php) PHP_BIN="/opt/lampp/bin/php" ;;   # common XAMPP path (adjust if necessary)
    --php-bin=*) PHP_BIN="${arg#*=}" ;;
    *) ;;
  esac
done

log() {
  if [ "$VERBOSE" = true ]; then
    printf "[composer.sh] %s\n" "$*"
  fi
}

# 1) ensure selected PHP exists
if ! command -v "$PHP_BIN" >/dev/null 2>&1; then
  err "PHP CLI ($PHP_BIN) is required but not found. Install PHP CLI or pass --php-bin=/path/to/php."
fi
log "Using PHP CLI: $($PHP_BIN -v | head -n1)"

# 2) ensure composer.phar is available and will be executed with chosen PHP
# Prefer to use local composer.phar executed by PHP_BIN for stability across PHP builds (XAMPP vs system).
if [ -f "$COMPOSER_PHAR" ]; then
  COMPOSER_CMD="$PHP_BIN $COMPOSER_PHAR"
  log "Using existing composer.phar with $PHP_BIN"
else
  # If project does not have composer.phar, try to download (best-effort)
  print "No composer.phar found in project. Attempting to download composer.phar (will be executed with $PHP_BIN)..."
  TMP_INSTALL="$ROOT/composer-setup.php"
  if ! command -v curl >/dev/null 2>&1; then
    err "curl required to download composer.phar. Install curl or put composer.phar in project root."
  fi

  curl -sS -o "$TMP_INSTALL" https://getcomposer.org/installer || err "Failed to download composer installer."
  HASH_EXPECTED=$(curl -sS https://composer.github.io/installer.sig || true)
  HASH_ACTUAL=$($PHP_BIN -r "echo hash_file('sha384', '$TMP_INSTALL');")
  if [ -n "$HASH_EXPECTED" ] && [ "$HASH_EXPECTED" != "$HASH_ACTUAL" ]; then
    print "WARNING: composer installer checksum mismatch — continuing anyway."
  fi

  # run installer with chosen PHP
  $PHP_BIN "$TMP_INSTALL" --quiet || { rm -f "$TMP_INSTALL"; err "Composer installer failed with $PHP_BIN."; }
  rm -f "$TMP_INSTALL"

  if [ -f "$COMPOSER_PHAR" ]; then
    COMPOSER_CMD="$PHP_BIN $COMPOSER_PHAR"
    log "Downloaded composer.phar and will run it with $PHP_BIN"
  else
    err "Failed to obtain composer.phar; please install composer or provide composer.phar in project root."
  fi
fi

# Helper wrapper to run composer through chosen PHP (keeps command output visible)
run_composer() {
  # "$@" are composer args, e.g. "install --no-interaction"
  $COMPOSER_CMD "$@"
}

# 3) composer install (reads composer.json) using the PHP-bound composer
print "Running: $COMPOSER_CMD install --no-interaction --no-progress"
run_composer install --no-interaction --no-progress

# 4) ensure mpdf is present in composer.json / vendor — require using the same composer (thus same PHP)
if [ "$DO_REQUIRE_MPDF" = true ]; then
  if grep -qi '"mpdf/mpdf"' composer.json >/dev/null 2>&1 || [ -d vendor/mpdf ]; then
    log "mpdf already required in composer.json or present in vendor/"
  else
    print "Requiring mpdf/mpdf via composer (with $PHP_BIN)..."
    run_composer require mpdf/mpdf --no-interaction --no-progress
  fi
else
  log "Skipping mpdf requirement as requested."
fi

# 5) regenerate autoload (with chosen composer)
print "Running: $COMPOSER_CMD dump-autoload --optimize --no-interaction"
run_composer dump-autoload --optimize --no-interaction

# 6) create upload dirs and set permissions
UPLOAD_DIRS=(public/uploads public/uploads/pdfs)
for d in "${UPLOAD_DIRS[@]}"; do
  if [ ! -d "$ROOT/$d" ]; then
    mkdir -p "$ROOT/$d"
    log "Created $d"
  fi
done

# set ownership to typical web users if present (requires sudo)
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

# make writable by owner and group (conservative)
chmod -R u+rwX,g+rwX "$ROOT/public/uploads" || true
log "Set upload dir permissions."

# 7) Optional DB / seed steps (use chosen PHP binary)
if [ "$DO_INSTALL_DB" = true ]; then
  if [ -f "$ROOT/scripts/setup_db.php" ]; then
    print "Initializing DB (scripts/setup_db.php) with $PHP_BIN..."
    $PHP_BIN "$ROOT/scripts/setup_db.php" || err "setup_db.php failed"
  else
    print "No scripts/setup_db.php found — skipping DB init."
  fi
fi

if [ "$DO_SEED" = true ]; then
  if [ -f "$ROOT/scripts/seed_demo_data.php" ]; then
    print "Seeding demo data (scripts/seed_demo_data.php) with $PHP_BIN..."
    # pass -v if requested
    if [ "$VERBOSE" = true ]; then
      $PHP_BIN "$ROOT/scripts/seed_demo_data.php" -v || err "seed_demo_data.php failed"
    else
      $PHP_BIN "$ROOT/scripts/seed_demo_data.php" || err "seed_demo_data.php failed"
    fi
  else
    print "No scripts/seed_demo_data.php found — skipping seed."
  fi
fi

print "Done. Composer & project setup complete."
