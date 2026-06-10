FROM php:8.2-apache

RUN docker-php-ext-install pdo_mysql mysqli \
    && a2enmod rewrite headers

COPY index.html /var/www/html/index.html
COPY 01/ /var/www/html/01/
COPY 02/ /var/www/html/02/
COPY 03/ /var/www/html/03/
COPY final/ /var/www/html/final/
COPY project_final/ /var/www/html/project_final/
COPY database/ /var/www/html/database/

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
