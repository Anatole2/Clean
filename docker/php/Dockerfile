FROM php:8.2-apache

# Installation des dépendances
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    libssl-dev \
    && docker-php-ext-install pdo pdo_mysql zip

RUN echo "date.timezone = Europe/Paris" > /usr/local/etc/php/conf.d/timezone.ini

# Xdebug
RUN pecl install xdebug && docker-php-ext-enable xdebug
ENV XDEBUG_MODE=coverage

# Installation de l'extension MongoDB
RUN pecl install mongodb && docker-php-ext-enable mongodb

# Changer le DocumentRoot vers /public
RUN sed -i 's#/var/www/html#/var/www/html/public#g' /etc/apache2/sites-available/000-default.conf

# Activer mod_rewrite et autoriser .htaccess
RUN a2enmod rewrite
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
