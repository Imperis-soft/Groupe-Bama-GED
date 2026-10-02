# Deux images construites depuis ce fichier (voir docker/build-push.sh) :
#   --target app   → imperissoft/grpbama        (PHP-FPM : application, file d'attente, planificateur)
#   --target nginx → imperissoft/grpbama-nginx  (nginx avec la conf et public/ intégrés)
# Toujours builder en --platform linux/amd64 (le VPS est en amd64).

# --- Stage 1 : Composer ---
FROM composer:2.6 AS composer

# --- Stage 2 : Base PHP ---
FROM php:8.2-fpm-alpine AS base

RUN apk add --no-cache \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    mariadb-client \
    su-exec \
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

# Outils d'aperçu et d'indexation des documents :
# - LibreOffice : aperçu PDF des PowerPoint, .doc, .odt, .rtf, TIFF (et texte des formats binaires)
# - poppler-utils (pdftotext, pdftoppm) : texte des PDF + rendu des pages scannées pour l OCR
# - tesseract : OCR des images et PDF scannés (recherche + détection des doublons)
RUN apk add --no-cache \
    libreoffice \
    ghostscript \
    poppler-utils \
    tesseract-ocr \
    tesseract-ocr-data-fra \
    tesseract-ocr-data-eng \
    ttf-dejavu \
    font-liberation \
    font-noto

# OPcache : validate_timestamps=0 → chaque déploiement = recréer le conteneur (pull + recreate)
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=20000'; \
    echo 'opcache.validate_timestamps=0'; \
} > /usr/local/etc/php/conf.d/opcache-optimized.ini

# Configuration PHP pour les uploads volumineux
RUN { \
    echo 'upload_max_filesize=150M'; \
    echo 'post_max_size=160M'; \
    echo 'memory_limit=256M'; \
    echo 'max_execution_time=300'; \
    echo 'max_input_time=300'; \
    echo 'expose_php=Off'; \
} > /usr/local/etc/php/conf.d/uploads.ini

# Pool PHP-FPM dimensionné pour un VPS partagé
COPY docker/php/zz-pool.conf /usr/local/etc/php-fpm.d/zz-pool.conf

# --- Stage 3 : Dépendances PHP ---
FROM base AS dependencies
WORKDIR /var/www
COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

# --- Stage 4 : Assets Vite ---
FROM node:20-alpine AS assets
WORKDIR /var/www
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY vite.config.js ./
COPY resources ./resources
# Tailwind scanne aussi les vues de pagination de Laravel (voir resources/css/app.css)
COPY --from=dependencies /var/www/vendor/laravel/framework/src/Illuminate/Pagination/resources/views \
     ./vendor/laravel/framework/src/Illuminate/Pagination/resources/views
RUN npm run build

# --- Stage 5 : Application (PHP-FPM) ---
FROM base AS app
WORKDIR /var/www

COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY --from=dependencies /var/www/vendor ./vendor
COPY --chown=www-data:www-data . .
COPY --from=assets --chown=www-data:www-data /var/www/public/build ./public/build

RUN mkdir -p storage/framework/cache/data \
             storage/framework/sessions \
             storage/framework/views \
             storage/app/public \
             storage/logs \
             bootstrap/cache \
    && COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload --optimize --classmap-authoritative --no-dev --no-scripts \
    && php artisan package:discover --ansi \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    && rm -f /usr/bin/composer \
    && rm -rf /tmp/* /var/tmp/*

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]

# --- Stage 6 : Nginx (conf + fichiers publics intégrés, aucun volume partagé) ---
FROM nginx:1.25-alpine AS nginx
COPY docker/nginx/grpbama.conf /etc/nginx/conf.d/default.conf
COPY --from=app /var/www/public /var/www/public
EXPOSE 80
