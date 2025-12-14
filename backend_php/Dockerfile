# =========================
# Stage 1: Build (Composer)
# =========================
FROM php:8.3-cli-alpine AS composer-builder
WORKDIR /app

# Composer binary
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Extensions needed for composer deps (gd/zip/intl for phpspreadsheet, etc.)
RUN apk add --no-cache \
    freetype-dev libjpeg-turbo-dev libpng-dev \
    icu-dev zlib-dev libzip-dev \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install -j$(nproc) gd intl zip

ENV COMPOSER_ALLOW_SUPERUSER=1

# Copy composer files first for caching
COPY composer.json composer.lock ./

# IMPORTANT: no scripts here because artisan not copied yet
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts

# Copy app source
COPY . .

# Optimized autoloader (still no scripts; we run artisan in entrypoint)
RUN composer dump-autoload --optimize --no-dev


# =========================
# Stage 2: Production
# =========================
FROM php:8.3-fpm-alpine

# System deps (gettext => envsubst)
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    gettext \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    zip \
    unzip \
    icu-dev \
    oniguruma-dev \
    postgresql-dev \
  && rm -rf /var/cache/apk/*

# PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    opcache

# Redis extension
RUN apk add --no-cache --virtual .build-deps autoconf g++ make \
  && pecl install redis \
  && docker-php-ext-enable redis \
  && apk del .build-deps

# Configs
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini
# Use template so we can inject $PORT at runtime via envsubst
COPY docker/nginx.conf.template /etc/nginx/nginx.conf.template
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# App
WORKDIR /var/www/html
COPY --from=composer-builder /app .

# Entrypoint
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Permissions & dirs
RUN mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache \
  && mkdir -p storage/logs bootstrap/cache \
  && chown -R www-data:www-data /var/www/html \
  && chmod -R 775 storage bootstrap/cache

# Render port (default fallback in entrypoint)
EXPOSE 10000

# Health check (uses $PORT with fallback)
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
  CMD sh -c "curl -fsS http://localhost:${PORT:-10000}/health || exit 1"

ENTRYPOINT ["/entrypoint.sh"]
