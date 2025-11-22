FROM php:8.2-apache

# Enable required PHP extensions for MySQL
RUN docker-php-ext-install mysqli pdo_mysql \
    && a2enmod rewrite

WORKDIR /var/www/html

# Copy application code into the image
COPY . /var/www/html

EXPOSE 80
