#!/bin/sh
# Démarrage des conteneurs Laravel.
#   php-fpm (défaut)  → attente MySQL, migrations, seeders si base vide, caches, puis PHP-FPM
#   autre commande    → (file d'attente, planificateur) attente MySQL, caches, puis la commande en www-data
set -e

cd /var/www
rm -f /tmp/ready

# Le volume storage peut être vide au premier démarrage
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/app/public storage/logs
chown -R www-data:www-data storage bootstrap/cache

# ── Attente de MySQL (30 × 3 s) ──────────────────────────────
echo "[entrypoint] Attente de MySQL sur ${DB_HOST}:${DB_PORT:-3306}…"
i=0
until php -r '
    try {
        new PDO("mysql:host=" . getenv("DB_HOST") . ";port=" . (getenv("DB_PORT") ?: 3306) . ";dbname=" . getenv("DB_DATABASE"),
                getenv("DB_USERNAME"), getenv("DB_PASSWORD"), [PDO::ATTR_TIMEOUT => 3]);
    } catch (Throwable $e) { fwrite(STDERR, $e->getMessage() . PHP_EOL); exit(1); }
' 2>/dev/null; do
    i=$((i + 1))
    if [ "$i" -ge 30 ]; then
        echo "[entrypoint] MySQL injoignable après 30 tentatives, abandon." >&2
        exit 1
    fi
    sleep 3
done
echo "[entrypoint] MySQL joignable."

artisan() { su-exec www-data php artisan "$@"; }

if [ "$1" = "php-fpm" ]; then
    # Migrations : une erreur arrête le démarrage (pas de base à moitié migrée en silence).
    # JAMAIS de migrate:fresh / db:wipe ici : le serveur MySQL est partagé.
    artisan migrate --force

    # Seeders uniquement sur une base vide (premier déploiement)
    users=$(php -r '
        $pdo = new PDO("mysql:host=" . getenv("DB_HOST") . ";port=" . (getenv("DB_PORT") ?: 3306) . ";dbname=" . getenv("DB_DATABASE"),
                       getenv("DB_USERNAME"), getenv("DB_PASSWORD"));
        echo (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    ')
    if [ "$users" = "0" ]; then
        echo "[entrypoint] Base vide : exécution des seeders."
        artisan db:seed --force
    fi
fi

artisan config:cache
artisan route:cache
artisan view:cache

if [ "$1" = "php-fpm" ]; then
    touch /tmp/ready
    exec php-fpm
fi

exec su-exec www-data "$@"
