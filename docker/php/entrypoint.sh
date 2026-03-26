#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

if [[ ! -f ".env" ]]; then
  echo "ERROR: .env not found in project root. Create it (or copy your provided .env) before running docker compose."
  exit 1
fi

# Ensure writable Laravel directories
mkdir -p storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache || true
chmod -R ug+rwX storage bootstrap/cache || true
chmod -R a+rX public || true

# Install PHP deps if needed (no migrations)
if [[ ! -d "vendor" ]]; then
  echo "Installing composer dependencies..."
  composer install --no-interaction --prefer-dist
fi

# Build Vite assets if needed (Filament often works without app assets,
# but this makes the admin UI reliable if your app references Vite bundles).
if [[ -f "package.json" ]]; then
  if [[ ! -f "public/build/manifest.json" ]]; then
    echo "Building frontend assets (Vite)..."
    if [[ ! -d "node_modules" ]]; then
      npm install
    fi
    npm run build
  fi
fi

# Some host filesystems / mounts do not allow symlinks; don't fail startup for that.

exec "$@"
