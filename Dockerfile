FROM php:8.2-apache

ARG UID=1000
ARG GID=1000

RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install mysqli gd zip \
    && rm -rf /var/lib/apt/lists/*

RUN groupmod -o -g "${GID}" www-data \
    && usermod -o -u "${UID}" -g "${GID}" www-data

WORKDIR /var/www/html
COPY --chown=www-data:www-data . /var/www/html

# Writable at build time for image-only runs (no bind mount); when the source
# is bind-mounted the modes come from the host and the uid remap above is what
# actually grants write access. The chown -R is not redundant with COPY --chown:
# that only covers the copied entries, while /var/www/html itself was created as
# uid 33 by the base image and the installer has to write config.php into it.
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} + \
    && find /var/www/html -type f -exec chmod 644 {} + \
    && chmod -R 775 /var/www/html/cache /var/www/html/uploads

EXPOSE 80

CMD ["apache2-foreground"]
