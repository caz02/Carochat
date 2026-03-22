FROM php:8.1-apache

# Install common extensions required by the app
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libonig-dev \
    libzip-dev \
    unzip \
    zlib1g-dev \
    git \
    && docker-php-ext-install pdo pdo_mysql mysqli gd zip

# Enable Apache rewrite
RUN a2enmod rewrite

COPY docker/php/000-default.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
