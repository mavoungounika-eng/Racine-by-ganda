# ═══════════════════════════════════════════════════════════════
# DOCKERFILE - Laravel Application (Production)
# ═══════════════════════════════════════════════════════════════

ARG PHP_VERSION=8.2

# ───────────────────────────────────────────────────────────────
# Stage 1: Base PHP-FPM
# ───────────────────────────────────────────────────────────────
FROM php:${PHP_VERSION}-fpm-alpine AS base

# Install system dependencies
RUN apk add --no-cache \
    bash \
    curl \
    git \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    postgresql-dev \
    icu-dev \
    linux-headers \
    fcgi \
    $PHPIZE_DEPS

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    opcache

# Install Redis extension
RUN pecl install redis-6.0.2 \
    && docker-php-ext-enable redis

# Enable PHP-FPM status page
RUN echo "pm.status_path = /status" >> /usr/local/etc/php-fpm.d/zz-docker.conf

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ───────────────────────────────────────────────────────────────
# Stage 2: Dependencies
# ───────────────────────────────────────────────────────────────
FROM base AS dependencies

WORKDIR /var/www/html

# Copy composer files
COPY composer.json composer.lock ./

# Install dependencies (production only)
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader

# ───────────────────────────────────────────────────────────────
# Stage 3: Production
# ───────────────────────────────────────────────────────────────
FROM base AS production

WORKDIR /var/www/html

# Copy application code
COPY --chown=www-data:www-data . .

# Copy dependencies from previous stage
COPY --from=dependencies --chown=www-data:www-data /var/www/html/vendor ./vendor

# Create required directories
RUN mkdir -p \
    storage/app/public \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

# Set permissions
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Optimize Laravel (Moved to entrypoint/runtime)
# RUN php artisan config:cache \
#     && php artisan route:cache \
#     && php artisan view:cache \
#     && php artisan event:cache

# Health check script
COPY docker/app/php-fpm-healthcheck /usr/local/bin/php-fpm-healthcheck
RUN chmod +x /usr/local/bin/php-fpm-healthcheck

# Switch to non-root user
USER www-data

# Expose PHP-FPM port
EXPOSE 9000

CMD ["php-fpm"]
