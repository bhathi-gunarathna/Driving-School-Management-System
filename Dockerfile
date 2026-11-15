FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

FROM php:8.3-apache AS app

RUN apt-get update && apt-get install -y --no-install-recommends \
        libcurl4-openssl-dev libfreetype6-dev libicu-dev libjpeg62-turbo-dev libpng-dev \
        libpq-dev libxml2-dev libzip-dev unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" bcmath curl dom exif gd intl mbstring opcache pcntl pdo_pgsql xml zip \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-progress --no-scripts --optimize-autoloader

COPY . .
COPY --from=assets /app/public/build ./public/build
COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY docker/entrypoint.sh /usr/local/bin/laravel-entrypoint

RUN chmod +x /usr/local/bin/laravel-entrypoint \
    && printf '%s\n' 'upload_max_filesize=10M' 'post_max_size=12M' 'expose_php=Off' > /usr/local/etc/php/conf.d/production.ini \
    && mkdir -p storage/app/private storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && php artisan package:discover --ansi \
    && chown -R www-data:www-data storage bootstrap/cache

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
EXPOSE 10000
ENTRYPOINT ["laravel-entrypoint"]
CMD ["apache2-foreground"]
