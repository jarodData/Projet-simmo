FROM php:8.1-apache

# Extensions PHP
RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip git curl libpng-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo pdo_mysql zip mbstring exif pcntl bcmath gd

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Activer mod_rewrite
RUN a2enmod rewrite

# Config Apache — fichier séparé pour éviter les problèmes d'échappement
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
COPY . .

RUN composer install --no-dev --optimize-autoloader

RUN if [ ! -f .env ]; then cp .env.example .env && php artisan key:generate; fi

RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 80
