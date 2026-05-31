#!/bin/bash
set -e

echo "==> Starting Spese application..."

# Warm up Symfony cache
echo "==> Warming up cache..."
php bin/console cache:clear --env=prod --no-debug --no-interaction 2>/dev/null || true
php bin/console cache:warmup --env=prod --no-debug --no-interaction 2>/dev/null || true

# Fix permissions
echo "==> Setting permissions..."
chown -R www-data:www-data var/

echo "==> Application ready!"

# Execute the CMD (apache2-foreground)
exec "$@"
