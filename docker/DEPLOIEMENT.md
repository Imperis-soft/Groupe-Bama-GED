# Déploiement — GED sur le VPS Imperissoft

Le VPS est partagé avec **Notaris** (stack `notaris_*`, port 8002, base `ntr2`). Rien de ce qui suit ne la touche.

| Élément            | GED                                   |
|--------------------|---------------------------------------------------|
| Stack Portainer    | `ged`                                         |
| Conteneurs         | `ged_app`, `ged_queue`, `ged_scheduler`, `ged_nginx` |
| Images             | `imperissoft/ged`, `imperissoft/ged-nginx` |
| Port hôte          | **8023** → nginx:80                               |
| Base               | `ged_db` / utilisateur `ged_user` sur le MySQL partagé (`mysql:3306`) |
| Réseaux            | `ged_network` (interne) + `db_mysql_mynetwork` (externe, PHP uniquement) |
| Volume             | `ged_storage` (logs, sessions, cache, fichiers temporaires) |
| Fichiers documents | MinIO (bucket privé), pas sur le VPS              |

## Fichiers

- `Dockerfile` — multi-stage : Composer → Vite (node:20) → `app` (PHP-FPM 8.2 + LibreOffice/OCR) → `nginx`
- `docker-compose.yml` — la stack Portainer
- `docker/nginx/ged.conf` — conf nginx intégrée à l'image nginx
- `docker/entrypoint.sh` — attente MySQL → migrations → seeders si base vide → caches → php-fpm
- `docker/php/zz-pool.conf` — pool PHP-FPM (16 workers max)
- `docker/build-push.sh` — build `linux/amd64` + push Docker Hub
- `docker/portainer.env.example` — variables à saisir dans Portainer
- `docker/mysql-init.sql` — création de la base et de l'utilisateur

## Premier déploiement

1. **Vérifier le port** sur le VPS : `ss -tlnp | grep 8023` (doit être vide).
2. **Base de données** : remplacer le mot de passe dans `docker/mysql-init.sql`, puis
   `docker exec -i mysql mysql -uroot -p < mysql-init.sql`
3. **Images** (sur le Mac) : `docker login` puis `./docker/build-push.sh`
4. **Portainer** → Stacks → *Add stack* nommée `ged` → coller `docker-compose.yml`
   → saisir les variables de `docker/portainer.env.example` → *Deploy*.
   Au premier démarrage, la base étant vide, les seeders créent le super admin et l'entreprise de démo :
   **changer leurs mots de passe immédiatement** (ils sont dans `database/seeders/UserSeeder.php`).
5. **Reverse proxy de l'hôte** : ajouter un vhost pour le nouveau domaine, **sans modifier celui de notaris.imperis.com** :

   ```nginx
   server {
       listen 80;
       server_name ged.imperis.com;
       client_max_body_size 160M;

       location / {
           proxy_pass http://127.0.0.1:8023;
           proxy_set_header Host $host;
           proxy_set_header X-Real-IP $remote_addr;
           proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
           proxy_set_header X-Forwarded-Proto $scheme;
           proxy_read_timeout 300s;
           proxy_send_timeout 300s;
           proxy_request_buffering off;
       }
   }
   ```

   puis `nginx -t && systemctl reload nginx` et `certbot --nginx -d ged.imperis.com`.
   Laravel fait déjà confiance au proxy (`trustProxies(at: '*')`) : les URLs sortent en https.

## Mise à jour

1. Sauvegarder la base avant toute migration risquée :
   `docker exec mysql mysqldump -uroot -p --single-transaction ged_db > ged_$(date +%F).sql`
2. `./docker/build-push.sh`
3. Portainer → stack `ged` → *Pull and redeploy* (ou `docker compose -p ged pull && docker compose -p ged up -d`).
   OPcache ne relit pas les fichiers : il faut **recréer** les conteneurs, un simple restart ne suffit pas.

Les migrations tournent automatiquement au démarrage de `ged_app`. En cas d'échec, le conteneur s'arrête
(voir `docker logs ged_app`) au lieu de démarrer sur une base à moitié migrée.

## Vérifications

```sh
docker logs ged_app --tail 50
docker ps --filter name=ged_          # 4 conteneurs « Up » (pas en Restarting)
curl -I http://localhost:8023/up          # 200
curl -I http://localhost:8002             # Notaris répond toujours
```

## Interdits (serveur MySQL partagé)

`migrate:fresh`, `migrate:reset`, `db:wipe`, `DROP DATABASE`, tout script agissant sur toutes les bases,
publication du port 3306, et toute modification de la stack Notaris ou de la base `ntr2`.
