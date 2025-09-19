# Use an official PHP image with Apache
FROM php:8.2-apache-bookworm

# 1. Install system dependencies & PHP extensions (for Laravel and SQLite)
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    sqlite3 \
    libsqlite3-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip pdo_sqlite sockets

# 2. Install Node.js (CRITICAL for npm run build for Vue/Inertia)
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs

# 3. Enable Apache's rewrite module (for pretty URLs)
RUN a2enmod rewrite

# 4. Copy the entire project into the container
COPY . /var/www/html

# 5. Set the working directory
WORKDIR /var/www/html

# 6. Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 7. Install PHP dependencies (NO DEV for production)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# 8. Install npm dependencies and build assets (Vue + Inertia)
RUN npm install
RUN npm run build

# 9. Set correct file permissions for Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# 10. Copy a custom Apache config (we'll create this next)
COPY .docker/000-default.conf /etc/apache2/sites-available/000-default.conf

# 11. Expose port 8080 (Render uses this port internally)
EXPOSE 8080