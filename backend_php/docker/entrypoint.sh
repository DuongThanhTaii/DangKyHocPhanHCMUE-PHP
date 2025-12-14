#!/bin/sh
set -e

echo "===== Starting Laravel Application ====="
cd /var/www/html

# Render PORT fallback
export PORT="${PORT:-10000}"

# Render nginx.conf từ template
if [ -f /etc/nginx/nginx.conf.template ]; then
  envsubst '${PORT}' < /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf
fi

# Bắt buộc APP_KEY
if [ -z "$APP_KEY" ]; then
  echo "ERROR: APP_KEY is not set. Set APP_KEY in Render env vars."
  exit 1
fi

echo "Optimizing application..."
php artisan config:cache

# route:cache hay fail nếu có Closure routes => đừng làm chết container
php artisan route:cache || echo "route:cache skipped (maybe closures)"
php artisan view:cache  || echo "view:cache skipped"

if [ -n "$DATABASE_URL" ] || [ -n "$DB_CONNECTION" ]; then
  echo "Running database migrations..."
  php artisan migrate --force || echo "Migration failed or no pending migrations"
fi

php artisan storage:link 2>/dev/null || true

chown -R www-data:www-data storage bootstrap/cache || true
chmod -R 775 storage bootstrap/cache || true

mkdir -p /var/log/supervisor /var/log/nginx

echo "===== Starting Supervisor ====="
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
