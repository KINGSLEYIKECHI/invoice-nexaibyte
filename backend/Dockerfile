FROM php:8.3-fpm-bookworm
RUN apt-get update && apt-get install -y --no-install-recommends git unzip libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev libonig-dev libxml2-dev libicu-dev libsqlite3-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install pdo_mysql pdo_sqlite bcmath gd mbstring zip intl \
 && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY . .
RUN composer install --no-interaction --prefer-dist --no-progress \
 && mkdir -p storage/app/public/logos storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs \
 && php artisan storage:link \
 && chown -R www-data:www-data storage bootstrap/cache
EXPOSE 9000
CMD ["php-fpm"]