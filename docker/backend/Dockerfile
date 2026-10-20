# ============================================================
# Stage 1: Build aset Vite backend (public/build untuk @vite)
# ============================================================
FROM node:20-alpine AS backend-assets

WORKDIR /app

COPY backend/package.json backend/package-lock.json ./
RUN npm ci

COPY backend/ .
RUN npm run build

# ============================================================
# Stage 2: PHP-FPM (Laravel)
# ============================================================
FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    unzip \
    curl \
    # Chromium headless untuk render PDF dari .docx (docx-preview)
    chromium \
    fontconfig \
    ttf-freefont

RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    xml

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/backend/php.ini "$PHP_INI_DIR/conf.d/99-lab.ini"

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --no-interaction --optimize-autoloader --no-progress

COPY backend/ .
COPY --from=backend-assets /app/public/build /var/www/html/public/build
COPY docker/backend/.env.docker .env

# Template dokumen default (base_path('../file/...'))
COPY file/ /var/www/file/

RUN mkdir -p storage/app/public storage/framework/{cache,sessions,views} storage/logs bootstrap/cache && \
    chmod -R 775 storage bootstrap/cache /var/www/file && \
    chown -R www-data:www-data storage bootstrap/cache /var/www/file

COPY docker/backend/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]