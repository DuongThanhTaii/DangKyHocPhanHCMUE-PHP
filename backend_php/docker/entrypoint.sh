#!/bin/sh
set -e

echo "===== Starting Laravel Application ====="

# Navigate to application directory
cd /var/www/html

# Generate application key if not set
if [ -z "$APP_KEY" ]; then
    echo "Generating application key..."
    php artisan key:generate --force
fi

# Clear and cache configuration for production
echo "Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run database migrations (if DATABASE_URL is set)
if [ -n "$DATABASE_URL" ] || [ -n "$DB_CONNECTION" ]; then
    echo "Running database migrations..."
    php artisan migrate --force || echo "Migration failed or no pending migrations"
fi

# Create storage link
php artisan storage:link 2>/dev/null || true

# Set proper permissions
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Create supervisor log directory
mkdir -p /var/log/supervisor
mkdir -p /var/log/nginx

echo "===== Starting Supervisor ====="
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
