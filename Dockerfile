# --- Stage 1: Composer ---
FROM composer:2.6 AS composer

# --- Stage 2: Base PHP ---
FROM php:8.2-fpm-alpine AS base

RUN apk add --no-cache \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    mariadb-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache

# Configuration OPcache
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=4000'; \
    echo 'opcache.revalidate_freq=2'; \
    echo 'opcache.fast_shutdown=1'; \
    echo 'opcache.validate_timestamps=0'; \
} > /usr/local/etc/php/conf.d/opcache-optimized.ini

# --- Stage 3: Dependances ---
FROM base AS dependencies
WORKDIR /var/www
COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# --- Stage 4: Production ---
FROM base AS production
WORKDIR /var/www

# On récupère composer (utile pour les hooks ou scripts artisan)
COPY --from=composer /usr/bin/composer /usr/bin/composer

# On copie d'abord les dépendances pour profiter du cache Docker
COPY --from=dependencies /var/www/vendor ./vendor

# Copie du code source avec les bonnes permissions directement
COPY --chown=www-data:www-data . .

# Script de démarrage
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Création des répertoires de cache et logs s'ils n'existent pas
# Et s'assurer que www-data a les droits dessus
RUN mkdir -p storage/framework/cache/data \
             storage/framework/sessions \
             storage/framework/views \
             storage/logs \
             bootstrap/cache \
    && chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Configurer PHP-FPM pour écouter sur toutes les interfaces (port 9000 par défaut)
RUN sed -i 's/listen = 127.0.0.1:9000/listen = 0.0.0.0:9000/' /usr/local/etc/php-fpm.d/www.conf

# Finalisation de l'autoloader (--no-scripts évite package:discover qui nécessite les deps dev)
RUN COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload --optimize --classmap-authoritative --no-dev --no-scripts

# Nettoyage final
RUN rm -rf /var/cache/apk/* /tmp/* /var/tmp/*

USER root

EXPOSE 9000

CMD ["/usr/local/bin/start.sh"]