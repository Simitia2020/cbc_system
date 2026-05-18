FROM php:8.2-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html && \
    chmod -R 775 /var/www/html/uploads /var/www/html/logs || true

EXPOSE 10000

RUN sed -ri -e 's!Listen 80!Listen 10000!g' /etc/apache2/ports.conf && \
    sed -ri -e 's!<VirtualHost \*:80>!<VirtualHost *:10000>!g' /etc/apache2/sites-available/000-default.conf

CMD ["apache2-foreground"]
