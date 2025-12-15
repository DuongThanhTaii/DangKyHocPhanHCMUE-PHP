#!/bin/sh
set -e

echo "===== Starting Laravel Application ====="
cd /var/www/html

# Render PORT fallback
export PORT="${PORT:-10000}"

# Render nginx.conf từ template (nếu có)
if [ -f /etc/nginx/nginx.conf.template ]; then
  envsubst '${PORT}' < /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf
fi

# Bắt buộc APP_KEY (production)
if [ -z "$APP_KEY" ]; then
  echo "ERROR: APP_KEY is not set. Set APP_KEY in Render env vars."
  exit 1
fi

echo "Clearing local caches..."
php artisan config:clear || true
php artisan view:clear   || true
php artisan route:clear  || true

# ⚠️ Redis (Upstash) không nên flush cache khi startup (cache:clear => FLUSHDB)
if [ "${CACHE_STORE}" = "redis" ] || [ "${CACHE_DRIVER}" = "redis" ]; then
  echo "Skipping cache:clear because cache store is redis (avoid FLUSHDB on Upstash)"
else
  php artisan cache:clear || true
fi

echo "Optimizing application..."
php artisan config:cache || true
php artisan route:cache  || echo "route:cache skipped (maybe closures)"
php artisan view:cache   || echo "view:cache skipped"

# ✅ DB dùng chung, chỉ read/write schema có sẵn => KHÔNG chạy migrate tự động
if [ "${RUN_MIGRATIONS}" = "true" ]; then
  echo "Running database migrations (RUN_MIGRATIONS=true)..."
  php artisan migrate --force
else
  echo "Skipping migrations (read/write existing schema)"
fi

php artisan storage:link 2>/dev/null || true

chown -R www-data:www-data storage bootstrap/cache || true
chmod -R 775 storage bootstrap/cache || true

mkdir -p /var/log/supervisor /var/log/nginx

echo "===== Starting Supervisor ====="
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
