#!/bin/sh

# Permissions
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Patch packages.php - remove dev packages
php -r "
\$file = '/var/www/bootstrap/cache/packages.php';
if (file_exists(\$file)) {
    \$p = include(\$file);
    foreach (['laravel/pail','laravel/sail','nunomaduro/collision','laravel/pint'] as \$d) {
        unset(\$p[\$d]);
    }
    file_put_contents(\$file, '<?php return ' . var_export(\$p, true) . ';');
}
"

# Laravel cache
php /var/www/artisan config:cache
php /var/www/artisan route:cache
php /var/www/artisan view:cache

# Migrations (on ignore les erreurs de colonnes déjà existantes)
php /var/www/artisan migrate --force 2>&1 | grep -v "already exists" || true

# Storage link
php /var/www/artisan storage:link --force 2>/dev/null || true

# Start PHP-FPM
exec php-fpm
