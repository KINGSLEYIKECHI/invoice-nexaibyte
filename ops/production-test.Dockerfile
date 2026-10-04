FROM php:8.3-apache
RUN apt-get update && apt-get install -y --no-install-recommends libpng-dev libjpeg62-turbo-dev libfreetype6-dev libonig-dev libxml2-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install bcmath gd mbstring pdo_mysql dom \
 && a2enmod rewrite headers \
 && rm -rf /var/lib/apt/lists/*
RUN printf 'disable_functions=proc_open\nexpose_php=Off\n' > /usr/local/etc/php/conf.d/production.ini \
 && printf 'Listen 8080\n' > /etc/apache2/ports.conf \
 && printf '<VirtualHost *:8080>\nDocumentRoot /srv/release/public\n<Directory /srv/release/public>\nAllowOverride All\nRequire all granted\nOptions FollowSymLinks\n</Directory>\n</VirtualHost>\n' > /etc/apache2/sites-available/000-default.conf
