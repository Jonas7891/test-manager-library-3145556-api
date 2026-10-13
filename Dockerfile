FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json ./
RUN composer install --no-interaction --no-scripts --no-autoloader --prefer-dist

FROM php:8.4-cli
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev unzip \
    && docker-php-ext-install pdo_pgsql \
    && rm -rf /var/lib/apt/lists/* \
    && printf "upload_max_filesize=4M\npost_max_size=8M\n" > /usr/local/etc/php/conf.d/uploads.ini
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY --from=vendor /app/vendor ./vendor
COPY . .
RUN composer dump-autoload --optimize \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chmod +x docker/entrypoint.sh
EXPOSE 8000
ENTRYPOINT ["docker/entrypoint.sh"]
