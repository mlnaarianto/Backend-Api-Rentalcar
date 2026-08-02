FROM php:8.4-fpm

# Argumen untuk menyamakan UID/GID dengan user di komputer host (Linux/Mac)
ARG user=www-data
ARG uid=1000
ARG gid=1000

# Ubah UID/GID user www-data agar sama dengan host untuk menghindari permission denied
RUN usermod -u ${uid} www-data && groupmod -g ${gid} www-data

# Install dependencies system
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libcurl4-openssl-dev \
    zip \
    unzip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libssl-dev \
    libicu-dev \
    ca-certificates \
    net-tools \
    iputils-ping \
    && rm -rf /var/lib/apt/lists/*

# Install ekstensi PHP
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mbstring \
    zip \
    exif \
    pcntl \
    bcmath \
    gd \
    curl \
    intl

# Update SSL certificates (penting untuk Google OAuth HTTPS)
RUN update-ca-certificates

# Fix permission /tmp agar Laravel bisa tulis cache Blade
RUN mkdir -p /tmp && chmod 1777 /tmp

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy project dengan kepemilikan langsung ke www-data
COPY --chown=www-data:www-data . .

# Install dependency Laravel
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Buat semua folder storage yang dibutuhkan Laravel
RUN mkdir -p /var/www/storage/framework/views \
             /var/www/storage/framework/cache \
             /var/www/storage/framework/sessions \
             /var/www/storage/app/public \
             /var/www/storage/logs \
             /var/www/bootstrap/cache

# Set permission yang benar
RUN chown -R www-data:www-data \
        /var/www/storage \
        /var/www/bootstrap/cache \
    && chmod -R 775 \
        /var/www/storage \
        /var/www/bootstrap/cache

# Clear semua cache saat build
RUN php artisan config:clear || true \
    && php artisan view:clear || true \
    && php artisan cache:clear || true

# Expose PHP-FPM
EXPOSE 9000

# Jalankan PHP-FPM sebagai www-data
CMD ["php-fpm"]