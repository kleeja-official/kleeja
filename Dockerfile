FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install mysqli gd zip
WORKDIR /var/www/html
COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

RUN chmod -R 777 /var/www/html/cache
RUN chmod -R 777 /var/www/html/uploads
RUN chmod -R 777 /var/www/html/uploads

EXPOSE 80

CMD ["apache2-foreground"]
