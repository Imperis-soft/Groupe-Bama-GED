-- Création de la base et de l'utilisateur dédiés à GED sur le MySQL PARTAGÉ.
-- À exécuter une seule fois, en root, depuis le VPS :
--   docker exec -i mysql mysql -uroot -p < docker/mysql-init.sql
-- Remplacer le mot de passe avant exécution (même valeur que DB_PASSWORD dans Portainer).
-- Ne touche à aucune autre base (Notaris = ntr2).

CREATE DATABASE IF NOT EXISTS ged_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'ged_user'@'%' IDENTIFIED BY 'CHANGER_MOI_MOT_DE_PASSE_FORT';

GRANT ALL PRIVILEGES ON ged_db.* TO 'ged_user'@'%';

FLUSH PRIVILEGES;
