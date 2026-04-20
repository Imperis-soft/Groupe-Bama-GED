# 🏗️ SYSTEM DESIGN — GED Groupe Bama

> **Document de référence technique** — Version 1.0 — Rédigé par l'équipe Imperis Sarl

---

## Introduction

Ce document décrit l'architecture complète du **Système de Gestion Électronique de Documents (GED)** développé sur mesure pour **Groupe Bama**, entreprise basée à Bamako, Mali. L'objectif de ce système est de centraliser, sécuriser et tracer l'ensemble du cycle de vie des documents de l'organisation, depuis leur création jusqu'à leur archivage ou suppression définitive.

Le projet est construit sur **Laravel 12** (PHP 8.2) avec une interface web full-stack. Il couvre des besoins métier avancés : workflow d'approbation multi-étapes, versioning de documents, signatures numériques, contrôle d'accès granulaire, indexation full-text avec OCR, et intégration WebDAV pour un accès natif depuis les systèmes d'exploitation. La richesse fonctionnelle le positionne comme une alternative custom à des solutions commerciales telles que SharePoint ou Alfresco.

Ce document s'adresse aux développeurs, architectes et responsables techniques souhaitant comprendre, maintenir ou faire évoluer le système.

---

## 1. ARCHITECTURE GLOBALE

L'application suit une architecture **monolithique MVC** déployée dans des conteneurs Docker. Le trafic entrant est géré par un reverse proxy **Nginx**, qui transmet les requêtes à **PHP-FPM** via le protocole FastCGI. Les fichiers binaires (documents DOCX, PDF, images) sont stockés sur un serveur **MinIO** compatible S3, totalement séparé de la base de données relationnelle. Cette séparation garantit que la base de données ne stocke que des métadonnées légères, tandis que le stockage objet gère les fichiers lourds de manière scalable.

```
┌─────────────────────────────────────────────────────────────────┐
│                        INTERNET / USERS                         │
└──────────────────────────┬──────────────────────────────────────┘
                           │ HTTPS
                    ┌──────▼──────┐
                    │   Nginx     │  (reverse proxy, port 8786)
                    │  1.25-alpine│
                    └──────┬──────┘
                           │ FastCGI (port 9000)
              ┌────────────▼────────────┐
              │     PHP-FPM 8.2         │
              │   Laravel 12 App        │
              │  (Docker Alpine)        │
              └──┬──────────┬───────────┘
                 │          │
        ┌────────▼──┐  ┌────▼──────────┐
        │  MySQL    │  │  MinIO (S3)   │
        │ (externe) │  │  Object Store │
        └───────────┘  └───────────────┘
```

### Stack technique complète

La stack a été choisie pour maximiser la productivité de développement tout en restant robuste en production. Laravel fournit le socle MVC, Tailwind CSS et Vue.js assurent une interface réactive sans framework lourd, et Vite garantit des builds rapides.

| Couche | Technologie |
|--------|-------------|
| Backend | Laravel 12, PHP 8.2 |
| Frontend | Blade + Tailwind CSS 4 + Vue.js (Headless UI, Lucide) |
| Build | Vite 7 |
| Base de données | MySQL (prod) / SQLite (dev) |
| Stockage fichiers | MinIO (S3-compatible, self-hosted) |
| Queue | Database driver (sync en prod) |
| Cache | File driver |
| Auth | Session web + Laravel Sanctum (API) |
| Conteneurs | Docker multi-stage + Docker Compose |
| Proxy | Nginx 1.25 |
| WebDAV | Sabre/DAV 4.7 |
| Génération DOCX | PHPOffice/PhpWord |
| QR Codes | Endroid/QrCode + SimpleSoftwareIO |
| OCR / Indexation | pdftotext + Tesseract (shell) |

---

## 2. SCHÉMA DE BASE DE DONNÉES COMPLET

La base de données est le cœur du système. Elle est organisée autour de l'entité `documents`, qui est reliée à l'ensemble des autres tables via des clés étrangères. Chaque relation a été pensée pour couvrir un besoin métier précis : traçabilité, collaboration, sécurité ou conformité.

Le schéma utilise les patterns suivants :
- **Soft deletes** sur `documents` et `document_comments` pour la corbeille
- **Colonnes JSON** pour les métadonnées flexibles, les tags et les workflows
- **Self-join** sur `categories` pour la hiérarchie parent/enfant
- **Relation polymorphe** sur `ged_notifications` pour lier les notifications à n'importe quelle entité
- **Pivot tables** pour les relations many-to-many (rôles, favoris)

```
┌──────────────────────────────────────────────────────────────────────────────┐
│                          SCHÉMA RELATIONNEL                                  │
└──────────────────────────────────────────────────────────────────────────────┘

users                          roles
├── id                         ├── id
├── full_name                  ├── name (admin|editor|viewer)
├── email (unique)             ├── display_name
├── phone                      └── description
├── address
└── password                   role_user (pivot)
                               ├── user_id → users.id
login_histories                └── role_id → roles.id
├── id
├── user_id → users.id
├── ip_address
├── user_agent
├── location
├── success (bool)
└── logged_at

categories
├── id
├── name
├── slug (unique)
├── description
└── parent_id → categories.id  (self-join, hiérarchie 2 niveaux)

documents  ← ENTITÉ CENTRALE
├── id
├── reference (BAMA-XXXXXX, unique)
├── title
├── file_path (chemin MinIO)
├── version (float)
├── status (draft|review|approved|archived)
├── is_confidential (bool)
├── retention_years
├── expires_at
├── archived_at
├── checksum (SHA256)
├── metadata (JSON)
├── tags (JSON array)
├── content_text (full-text indexé)
├── approval_workflow (JSON)
├── category_id → categories.id
├── creator_id → users.id
└── deleted_at (soft delete)

document_versions
├── id
├── document_id → documents.id
├── version_number
├── file_path (MinIO)
├── checksum (SHA256)
├── change_description
├── created_by → users.id
└── metadata (JSON: name, size, mime_type)

document_audit_logs
├── id
├── document_id → documents.id
├── user_id → users.id
├── action (viewed|created|updated|deleted|archived|approved|rejected|...)
├── description
├── old_values (JSON)
├── new_values (JSON)
├── ip_address
└── user_agent

document_permissions  (ACL granulaire)
├── id
├── document_id → documents.id
├── user_id → users.id
├── can_view / can_edit / can_delete
├── can_approve / can_archive
├── can_share / can_comment
├── granted_by → users.id
└── expires_at (permissions temporaires)

document_shares
├── id
├── document_id → documents.id
├── shared_by → users.id
├── shared_with → users.id  (nullable = lien public)
├── share_token (48 chars, unique)
├── access_level (view|edit|comment)
├── message
├── expires_at
├── accessed_at
└── is_active

approval_steps
├── id
├── document_id → documents.id
├── approver_id → users.id
├── step_order
├── status (pending|approved|rejected|skipped)
├── comment
├── decided_at
└── due_at

document_comments
├── id
├── document_id → documents.id
├── user_id → users.id
├── parent_id → document_comments.id  (réponses imbriquées)
├── content
├── type (comment|annotation|approval_note|rejection_note)
├── is_internal (bool, visible admins seulement)
├── edited_at
└── deleted_at (soft delete)

document_locks
├── id
├── document_id → documents.id  (unique)
├── locked_by → users.id
├── lock_token (UUID)
├── locked_at
└── expires_at

document_signatures
├── id
├── document_id → documents.id
├── user_id → users.id
├── signature_data (base64 PNG)
├── signature_hash (SHA256)
├── ip_address / user_agent
├── page_number
├── position (JSON: x, y, width, height)
├── status (pending|signed|rejected)
├── reason
└── signed_at

document_verifications
├── id
├── document_id → documents.id
├── verification_code (32 chars, unique)
├── verified_at
├── device_info (JSON)
├── ip_address
└── user_agent

document_favorites (pivot)
├── user_id → users.id
└── document_id → documents.id

ged_notifications
├── id
├── user_id → users.id
├── type (approval_needed|document_shared|comment_added|approval_rejected|...)
├── title / message / link
├── notifiable_type + notifiable_id  (polymorphe → Document)
├── is_read / read_at
└── email_sent

settings  (key-value store)
├── key
└── value
```

---

## 3. FLUX MÉTIER PRINCIPAUX

Cette section décrit les processus métier clés du système, c'est-à-dire les enchaînements d'actions qui produisent de la valeur pour l'utilisateur. Chaque flux est représenté sous forme de diagramme pour faciliter la compréhension des transitions d'état et des acteurs impliqués.

### 3.1 Cycle de vie d'un document

Un document passe par plusieurs statuts depuis sa création jusqu'à sa suppression définitive. Chaque transition est loguée dans `document_audit_logs` avec l'utilisateur responsable, l'horodatage, l'adresse IP et les valeurs avant/après.

```
                    ┌─────────────┐
                    │   CRÉATION  │
                    │  (draft)    │◄── Import DOCX existant
                    │             │◄── Génération depuis template
                    └──────┬──────┘
                           │ setup workflow
                    ┌──────▼──────┐
                    │   REVIEW    │◄── Approbateurs notifiés
                    │  (review)   │    par email + in-app
                    └──┬──────┬───┘
               approve │      │ reject
                    ┌──▼──┐ ┌─▼──────┐
                    │APPRO│ │ DRAFT  │◄── Créateur notifié
                    │UVÉE │ │(retour)│
                    └──┬──┘ └────────┘
                       │ archive
                    ┌──▼──────────┐
                    │  ARCHIVÉE   │
                    └──┬──────────┘
                       │ expiration auto
                    ┌──▼──────────┐
                    │  CORBEILLE  │  (soft delete)
                    └──┬──────────┘
                       │ force delete (admin)
                    ┌──▼──────────┐
                    │  SUPPRIMÉE  │  (fichier MinIO supprimé)
                    └─────────────┘
```

### 3.2 Workflow d'approbation séquentielle

Le workflow d'approbation permet de soumettre un document à une chaîne d'approbateurs ordonnés. Chaque approbateur est notifié par email et via les notifications in-app. Le document ne passe au statut `approved` que lorsque **toutes** les étapes sont validées. Un seul rejet suffit à renvoyer le document en `draft` avec notification du créateur.

```
Document → setup(approvers[A, B, C]) → status: review

Step 1 (A) pending ──► approved ──► Step 2 (B) pending ──► approved ──► Step 3 (C) pending
                                                                                    │
                                                                              approved
                                                                                    │
                                                                     Document status: approved
                                                                     Créateur notifié ✓

Si rejet à n'importe quelle étape:
    Step N → rejected → Document status: draft → Créateur notifié avec raison
```

### 3.3 Pipeline d'indexation full-text

Lors de chaque upload ou modification de fichier, un job asynchrone (`IndexDocumentText`) est dispatché en queue. Ce job télécharge le fichier depuis MinIO vers un répertoire temporaire, extrait le texte selon le format du fichier, puis met à jour le champ `content_text` du document. Ce champ alimente ensuite le moteur de recherche full-text de PostgreSQL via un vecteur `tsvector` configuré en français.

```
Upload fichier (DOCX / PDF / Image)
        │
        ▼
Storage::disk('s3')->put(path, stream)
        │
        ▼
IndexDocumentText::dispatch(documentId)   [Job asynchrone]
        │
        ├── DOCX → ZipArchive → word/document.xml → strip_tags → content_text
        ├── PDF  → pdftotext (shell) → content_text
        └── IMG  → tesseract OCR (shell) → content_text
                │
                ▼
        Document::update(['content_text' => $text])
                │
                ▼
        PostgreSQL tsvector (french config)
        → Recherche via plainto_tsquery('french', ?)
```

### 3.4 Système de vérification d'authenticité

À la création de chaque document, un code de vérification unique (32 caractères aléatoires) est généré et stocké dans `document_verifications`. Un QR code pointant vers l'URL `/verify/{code}` est automatiquement intégré dans le pied de page du fichier DOCX généré. N'importe qui disposant du document imprimé peut scanner ce QR code pour confirmer son authenticité sans avoir besoin d'un compte sur la plateforme.

```
Création document
        │
        ▼
verification_code = Str::random(32)
        │
        ▼
QR Code généré → URL: /verify/{code}
        │
        ▼
Intégré dans le pied de page DOCX (via PhpWord)
        │
        ▼
Utilisateur scanne QR → GET /verify/{code}
        │
        ├── Trouvé     → Affiche infos document (titre, ref, date, créateur)
        └── Non trouvé → Page "document invalide"
```

---

## 4. ARCHITECTURE DES COUCHES

L'application est structurée en couches distinctes qui séparent clairement les responsabilités. Les contrôleurs gèrent uniquement la logique HTTP (validation, réponse). La logique métier complexe est déléguée aux services. Les traitements lourds sont isolés dans des jobs de queue. Cette organisation facilite la maintenance et l'évolution du code.

### 4.1 Contrôleurs (21 controllers)

Chaque contrôleur est responsable d'un domaine fonctionnel précis. Ils ne contiennent pas de logique métier directe : ils délèguent aux services (`DocumentArchivalService`, `NotificationService`) pour les opérations complexes.

| Controller | Responsabilité |
|---|---|
| `AuthController` | Login / Logout (session) |
| `PasswordResetController` | Forgot / Reset password |
| `ProfileController` | Profil utilisateur |
| `DashboardController` | Métriques + activité récente |
| `DocumentController` | CRUD + versions + audit + preview + edit-online |
| `BulkDocumentController` | Opérations en masse (archive/delete/approve) |
| `CategoryController` | CRUD catégories hiérarchiques |
| `UserController` | CRUD utilisateurs + gestion rôles |
| `ApprovalController` | Setup workflow + approve/reject |
| `DocumentShareController` | Partage interne + lien public |
| `DocumentCommentController` | Commentaires + réponses imbriquées |
| `DocumentSignatureController` | Signatures numériques |
| `DocumentLockController` | Verrous d'édition (acquire/release/status) |
| `DocumentFavoriteController` | Toggle favoris |
| `DocumentVerificationController` | Vérification QR |
| `NotificationController` | Notifications in-app |
| `TrashController` | Corbeille (restore/force-delete) |
| `ReportController` | Rapports + export CSV |
| `SiteConfigController` | Settings (mail, notifs, etc.) |
| `WebDavController` | Accès WebDAV |

### 4.2 Services métier

Les services encapsulent la logique métier réutilisable et complexe. Ils sont injectés dans les contrôleurs via le conteneur IoC de Laravel. Cette approche évite la duplication de code et centralise les règles métier critiques comme l'audit trail et le versioning.

**`DocumentArchivalService`** — Gère tout ce qui touche à la vie du fichier physique et à sa traçabilité.

```
DocumentArchivalService
├── createVersion()      → Crée une nouvelle version avec checksum SHA256, met à jour le document principal
├── archiveDocument()    → Passe le statut à "archived", enregistre archived_at + audit log
├── restoreVersion()     → Restaure file_path et checksum d'une version antérieure
├── verifyIntegrity()    → Recalcule le SHA256 du fichier MinIO et compare au checksum stocké
├── cleanupExpired()     → Supprime physiquement les fichiers MinIO des documents expirés
└── logAction()          → Point d'entrée unique pour tous les audit logs (action, old/new values, IP, UA)

NotificationService
├── notify()             → Crée une entrée GedNotification + envoie email si SMTP activé dans settings
├── notifyApprovers()    → Itère sur les ApprovalStep pending et notifie chaque approbateur
├── notifyRejection()    → Notifie le créateur du document avec la raison du rejet
├── notifyShare()        → Notifie l'utilisateur destinataire d'un partage
└── notifyComment()      → Notifie le créateur du document lors d'un nouveau commentaire
     └── La configuration SMTP est lue dynamiquement depuis la table settings à chaque appel
```

### 4.3 Jobs (Queue)

Les jobs permettent de déporter les traitements lourds hors du cycle requête/réponse HTTP. Le job `IndexDocumentText` est le seul job actuellement implémenté. Il est dispatché automatiquement à chaque création ou modification de document.

> ⚠️ En production, la queue est configurée en mode `sync`, ce qui signifie que le job s'exécute de manière synchrone dans la même requête HTTP. Pour une vraie asynchronicité, il faudrait passer à un driver `database` ou `redis` avec un worker dédié (`php artisan queue:work`).

```
IndexDocumentText (ShouldQueue, timeout: 120s)
├── Télécharge fichier depuis MinIO → /tmp
├── Détecte extension (docx / pdf / image)
├── Extrait texte:
│   ├── DOCX → ZipArchive + strip_tags
│   ├── PDF  → pdftotext (shell_exec)
│   └── IMG  → tesseract (shell_exec)
└── Met à jour content_text → tsvector PostgreSQL
```

### 4.4 Commandes Artisan

Ces commandes sont conçues pour être exécutées en tâche planifiée (cron) ou manuellement par un administrateur système. Elles automatisent la maintenance du cycle de vie documentaire.

```
documents:cleanup-expired       → Parcourt les documents dont expires_at < now() et les supprime
                                  physiquement de MinIO. Supporte --dry-run pour simulation.
documents:regenerate-qrcodes    → Relit les fichiers DOCX depuis MinIO, régénère les QR codes
                                  de vérification et réupload. Supporte --id pour cibler un document.
```

### 4.5 Middleware

Le middleware `RoleMiddleware` est le gardien des routes sensibles. Il vérifie que l'utilisateur authentifié possède le rôle requis avant de laisser passer la requête. En cas d'échec, il retourne une erreur HTTP 403.

```
auth          → Vérifie qu'une session active existe (guard web de Laravel)
role:admin    → Vérifie que l'utilisateur a le rôle "admin" via User::hasRole()
role:editor   → Vérifie que l'utilisateur a le rôle "editor"
```

### 4.6 Validation personnalisée

Deux règles de validation custom ont été créées pour garantir l'intégrité des données JSON complexes soumises via les formulaires.

```
ValidApprovalWorkflow   → Vérifie que le JSON soumis respecte la structure attendue
                          pour un workflow d'approbation (tableau d'étapes avec approver_id)
ValidMetadata           → Vérifie que le JSON soumis est un objet clé/valeur valide
                          sans structures imbriquées non autorisées
```

---

## 5. CONTRÔLE D'ACCÈS (RBAC + ACL)

Le système implémente deux niveaux de contrôle d'accès complémentaires. Le premier niveau est le **RBAC** (Role-Based Access Control) : trois rôles globaux définissent ce qu'un utilisateur peut faire sur l'ensemble de la plateforme. Le second niveau est l'**ACL** (Access Control List) : des permissions individuelles peuvent être accordées par document, permettant de donner à un utilisateur `viewer` la capacité d'éditer un document spécifique, ou de définir des droits temporaires avec une date d'expiration.

### Matrice des permissions par rôle

| Action | Admin | Editor | Viewer |
|--------|:-----:|:------:|:------:|
| Créer document | ✓ | ✓ | ✗ |
| Modifier document | ✓ | ✓* | ✗ |
| Supprimer document | ✓ | ✗ | ✗ |
| Archiver document | ✓ | ✓ | ✗ |
| Approuver document | ✓ | ✓* | ✗ |
| Gérer utilisateurs | ✓ | ✗ | ✗ |
| Voir rapports | ✓ | ✗ | ✗ |
| Vider corbeille | ✓ | ✗ | ✗ |
| Voir documents | ✓ | ✓ | ✓ |
| Télécharger | ✓ | ✓ | ✓ |
| Commenter | ✓ | ✓ | ✓* |

> `*` = si ACL document le permet

### ACL granulaire par document (`document_permissions`)

Lorsque les rôles globaux ne suffisent pas, un administrateur peut accorder des permissions spécifiques à un utilisateur sur un document précis. Ces permissions peuvent être temporaires grâce au champ `expires_at`. La méthode `Document::canEdit()` et `Document::canApprove()` vérifient d'abord le rôle global, puis consultent l'ACL si nécessaire.

```
can_view | can_edit | can_delete | can_approve | can_archive | can_share | can_comment
+ expires_at  →  permissions temporaires avec auto-expiration (isExpired() vérifié à chaque accès)
```

---

## 6. ROUTES & ENDPOINTS

L'application expose deux types de routes : les **routes web** (rendu Blade, session-based) et les **routes API** (réponses JSON, utilisées en AJAX depuis les vues). Les routes publiques sont accessibles sans authentification et couvrent les cas d'usage externes : vérification de document par QR code, accès par lien partagé, et réinitialisation de mot de passe.

### Routes publiques

```
GET  /login                     → Formulaire login
POST /login                     → Authentification
GET  /verify/{code}             → Vérification QR document
POST /verify/{code}             → Soumettre vérification
GET  /share/{token}             → Accès document par lien public
GET  /forgot-password           → Formulaire mot de passe oublié
POST /forgot-password           → Envoi email reset
GET  /reset-password/{token}    → Formulaire reset
POST /reset-password            → Mise à jour mot de passe
```

### Routes protégées (auth)

```
GET  /dashboard

# Documents
GET    /documents                           → Liste + recherche + filtres
GET    /documents/advanced-search          → Recherche avancée
POST   /documents/create                   → Créer (template ou import)
GET    /documents/{id}                     → Détail
GET    /documents/{id}/edit                → Formulaire édition
PUT    /documents/{id}                     → Mettre à jour
DELETE /documents/{id}                     → Soft delete (corbeille)
POST   /documents/{id}/archive             → Archiver
GET    /documents/{id}/download            → Télécharger DOCX
GET    /documents/{id}/stream              → Stream DOCX (proxy MinIO)
GET    /documents/{id}/preview             → Aperçu HTML (Mammoth.js)
GET    /documents/{id}/edit-online         → Éditeur en ligne
POST   /documents/{id}/save-online         → Sauvegarder depuis éditeur
POST   /documents/{id}/upload-version      → Upload nouvelle version
GET    /documents/{id}/versions            → Historique versions
POST   /documents/{id}/versions/{v}/restore → Restaurer version
GET    /documents/{id}/audit               → Journal d'audit

# Workflow d'approbation
GET    /documents/{id}/approval            → Vue workflow
POST   /documents/{id}/approval/setup      → Configurer approbateurs
POST   /documents/{id}/approval/{step}/approve → Approuver étape
POST   /documents/{id}/approval/{step}/reject  → Rejeter étape

# Partages
GET    /documents/{id}/shares              → Liste partages
POST   /documents/{id}/shares              → Créer partage
DELETE /documents/{id}/shares/{share}      → Révoquer partage

# Commentaires
POST   /documents/{id}/comments            → Ajouter commentaire
PUT    /documents/{id}/comments/{comment}  → Modifier
DELETE /documents/{id}/comments/{comment}  → Supprimer

# Signatures
GET    /documents/{id}/signatures          → Liste signatures
POST   /documents/{id}/signatures          → Signer
GET    /documents/{id}/signatures/{sig}/verify → Vérifier signature

# Verrous
POST   /documents/{id}/lock                → Acquérir verrou
DELETE /documents/{id}/lock                → Libérer verrou
GET    /documents/{id}/lock/status         → Statut verrou

# Favoris
POST   /documents/{id}/favorite            → Toggle favori
GET    /favorites                          → Mes favoris

# Opérations en masse
POST   /documents/bulk                     → archive/delete/approve en masse

# Catégories
GET|POST        /categories
GET|PUT|DELETE  /categories/{id}

# Utilisateurs (admin)
GET|POST        /users
GET|PUT|DELETE  /users/{id}
GET|PUT         /users/{id}/roles

# Notifications
GET    /notifications
POST   /notifications/{id}/read
POST   /notifications/read-all
GET    /notifications/unread-count
DELETE /notifications/{id}

# Corbeille
GET    /trash
POST   /trash/{id}/restore
DELETE /trash/{id}/force          (admin)
DELETE /trash/empty               (admin)

# Rapports (admin)
GET    /reports
GET    /reports/export-csv
GET    /reports/export-audit

# Profil
GET|PUT /profile
PUT     /profile/password

# Settings (admin)
GET|POST /settings
```

### API interne (AJAX)

Ces endpoints sont appelés depuis les vues JavaScript (Vue.js / Axios) pour alimenter les composants de recherche avancée et les modales de prévisualisation sans rechargement de page.

```
GET /api/documents/search       → Recherche avancée JSON (filtres: q, category, status, creator, tags, date_from, date_to)
GET /api/documents/{id}         → Détail document JSON avec relations category et creator
```

---

## 7. INFRASTRUCTURE & DÉPLOIEMENT

L'infrastructure est entièrement conteneurisée avec Docker. Le Dockerfile utilise un build **multi-stage** pour produire une image de production légère et optimisée, sans les dépendances de développement. Le déploiement en production se fait via Docker Compose, avec deux services principaux (PHP-FPM et Nginx) et un stockage objet MinIO externe géré par Imperis Sarl.

### 7.1 Dockerfile multi-stage

Le build en quatre étapes permet de séparer les outils de build (Composer) de l'image finale, réduisant significativement la taille de l'image et la surface d'attaque.

```
Stage 1: composer:2.6
    └── binaire composer

Stage 2: php:8.2-fpm-alpine (base)
    └── Extensions: pdo_mysql, mbstring, gd, zip, intl,
                    opcache, bcmath, pcntl, exif

Stage 3: dependencies
    └── composer install --no-dev --no-scripts --no-autoloader

Stage 4: production
    ├── composer dump-autoload --optimize --classmap-authoritative
    ├── OPcache: 128MB, validate_timestamps=0, fast_shutdown=1
    └── start.sh:
        ├── config:cache + route:cache + view:cache
        ├── migrate --force
        └── exec php-fpm
```

### 7.2 Docker Compose

En production, l'application tourne sur le serveur d'Imperis Sarl. Le service `app` (PHP-FPM) et le service `web` (Nginx) communiquent via un réseau bridge interne `laravel_network`. La base de données MySQL est partagée avec d'autres applications via le réseau externe `db_mysql_mynetwork`, ce qui évite de dupliquer l'instance MySQL.

```
Services:
  app  → PHP-FPM (imperissoft/grpbama:latest)
         Healthcheck: php-fpm -t (30s interval)
  web  → Nginx 1.25-alpine (port 8786:80)
         Healthcheck: wget http://localhost/ (30s interval)

Volumes:
  storage_data   → /var/www/storage  (sessions, logs, cache)
  cache_data     → /var/www/bootstrap/cache
  public_assets  → /var/www/public   (assets Vite buildés)

Networks:
  laravel_network     → bridge interne app ↔ nginx
  db_mysql_mynetwork  → externe (MySQL partagé)
```

### 7.3 Stockage objet MinIO

MinIO est utilisé comme alternative self-hosted à AWS S3. Il expose une API compatible S3, ce qui permet d'utiliser le driver `league/flysystem-aws-s3-v3` de Laravel sans modification. Tous les fichiers DOCX sont stockés dans le bucket `groupebama` avec une organisation par préfixe `documents/`. Le mode `path-style` est activé car MinIO ne supporte pas le style `virtual-hosted` par défaut.

```
Bucket:   groupebama
Endpoint: https://api.storage.imperis.com
Mode:     path-style (AWS_USE_PATH_STYLE_ENDPOINT=true)

Structure:
  documents/
    ├── BAMA-XXXXXX.docx        (document v1)
    ├── BAMA-XXXXXX_v2.docx     (version 2)
    └── BAMA-XXXXXX_v3.docx     (version 3)
```

---

## 8. FONCTIONNALITÉS AVANCÉES

Cette section détaille les fonctionnalités qui dépassent le CRUD classique et constituent la valeur ajoutée principale du système par rapport à une solution documentaire basique.

### 8.1 Édition en ligne (DOCX dans le navigateur)

L'édition en ligne permet à un utilisateur de modifier le contenu d'un document DOCX directement dans son navigateur, sans installer Microsoft Word. Le flux repose sur deux bibliothèques complémentaires : **Mammoth.js** côté client pour convertir le DOCX en HTML éditable, et **PhpWord** côté serveur pour reconvertir le HTML modifié en DOCX valide avant de le réuploader sur MinIO.

```
GET /documents/{id}/stream
    → Proxy MinIO → navigateur (contourne CORS)
    → Mammoth.js convertit DOCX → HTML côté client

Édition HTML dans le navigateur
    → POST /documents/{id}/save-online
    → PhpWord::addHtml() reconvertit HTML → DOCX
    → Upload MinIO + version créée + IndexDocumentText dispatché
```

### 8.2 WebDAV

L'intégration WebDAV via **Sabre/DAV 4.7** permet aux utilisateurs d'accéder aux documents directement depuis leur explorateur de fichiers (Windows Explorer, macOS Finder) sans passer par l'interface web. Chaque document est accessible via son propre endpoint `/webdav/{id}`, supportant les méthodes GET (lecture), PUT (écriture), PROPFIND (métadonnées) et OPTIONS.

```
GET|PUT|OPTIONS|HEAD|PROPFIND /webdav/{id}
    → Sabre/DAV 4.7
    → Accès direct depuis Windows Explorer / macOS Finder
```

### 8.3 Partage par lien public

Le partage par lien permet de donner accès à un document à une personne externe sans qu'elle ait besoin d'un compte sur la plateforme. Un token de 48 caractères aléatoires est généré et associé à un niveau d'accès (`view`, `edit`, `comment`) et une date d'expiration optionnelle. L'accès est révocable à tout moment par le propriétaire du partage.

```
POST /documents/{id}/shares
    → share_token = Str::random(48)
    → expires_at configurable
    → access_level: view | edit | comment

GET /share/{token}
    → Accès sans authentification
    → Vérifie is_active + expires_at
    → Enregistre accessed_at
```

### 8.4 Signatures numériques

Les signatures numériques permettent à un utilisateur de signer électroniquement un document via un canvas HTML5. La signature dessinée est capturée en base64 PNG, puis un hash SHA256 est calculé sur cette donnée pour garantir son intégrité. La signature est stockée avec les métadonnées légales nécessaires (IP, user-agent, horodatage, position sur la page) et peut être vérifiée ultérieurement.

```
Canvas HTML5 → base64 PNG
    → signature_hash = SHA256(signature_data)
    → Stocké avec: ip_address, user_agent, page_number, position (JSON)
    → Vérifiable via GET /documents/{id}/signatures/{sig}/verify
```

### 8.5 Verrous d'édition collaboratif

Pour éviter les conflits d'édition simultanée, le système implémente un mécanisme de verrous optimistes. Lorsqu'un utilisateur commence à éditer un document, il acquiert un verrou identifié par un UUID. Ce verrou a une durée de vie configurable et se libère automatiquement à expiration. Un seul verrou actif est possible par document (contrainte `unique` en base). Seul le propriétaire du verrou ou un administrateur peut le libérer manuellement.

```
POST   /documents/{id}/lock   → lock_token = UUID, expires_at configurable
DELETE /documents/{id}/lock   → Libère (owner ou admin)
GET    /documents/{id}/lock/status → État + propriétaire + expiration
Contrainte: un seul verrou actif par document (unique constraint)
```

### 8.6 Rapports & Analytics

Le module de rapports est réservé aux administrateurs. Il fournit une vue consolidée de l'activité documentaire de l'organisation, avec des métriques en temps réel calculées directement depuis la base de données. Les exports CSV incluent un BOM UTF-8 pour garantir la compatibilité avec Microsoft Excel.

```
Dashboard:
├── Total documents / catégories / utilisateurs
├── Répartition par statut (draft / review / approved / archived)
├── Documents expirés / confidentiels
├── Activité récente (10 derniers audit logs)
└── Santé système (DB + MinIO + disk free)

Reports (admin):
├── Stats globales par statut
├── Top 10 utilisateurs actifs (audit logs)
├── Documents par catégorie
├── Activité 30 derniers jours (courbe daily)
├── Documents expirés / expirant dans 30j
├── Export CSV documents (filtrable par statut/catégorie)
└── Export CSV audit trail (5 000 lignes max, BOM UTF-8 Excel)
```

---

## 9. CATÉGORIES DOCUMENTAIRES

Le système est livré avec une taxonomie documentaire prédéfinie, adaptée aux besoins d'une entreprise africaine de taille intermédiaire. Cette taxonomie couvre 10 domaines métier avec plus de 40 sous-catégories, organisées en hiérarchie parent/enfant (deux niveaux maximum). Elle peut être étendue ou modifiée directement depuis l'interface d'administration.

10 domaines métier, 40+ sous-catégories prédéfinies :

| Domaine | Sous-catégories |
|---------|----------------|
| Administration & Direction | PV, Décisions, Correspondances, Rapports annuels, Organigrammes |
| Ressources Humaines | Contrats, Fiches de paie, Dossiers, Formations, Évaluations, Congés, RI |
| Finance & Comptabilité | Factures, Bons de commande, Budgets, Bilans, Déclarations fiscales, Relevés, Notes de frais |
| Juridique & Conformité | Contrats commerciaux, Actes notariés, Licences, Contentieux, RGPD, Assurances |
| Projets & Opérations | CDC, Plans, Rapports d'avancement, Livrables, Procédures |
| Qualité & Normes | Manuel qualité, Procédures, Audits, Certifications, Non-conformités |
| Commercial & Marketing | Offres, Présentations, Études de marché, Supports, Rapports commerciaux |
| Informatique & Technique | Architectures, Manuels, Specs techniques, Sécurité, Maintenance |
| Achats & Logistique | Appels d'offres, Contrats fournisseurs, Bons de réception, Inventaires |
| Archives | Archives admin / financières / techniques |

---

## 10. SÉCURITÉ

La sécurité est traitée en profondeur à plusieurs niveaux : authentification, autorisation, intégrité des données, traçabilité et protection contre les suppressions accidentelles. Chaque mécanisme répond à un risque identifié dans le contexte d'une GED d'entreprise.

| Mécanisme | Implémentation | Risque couvert |
|-----------|---------------|----------------|
| Authentification | Session Laravel + bcrypt (12 rounds) | Accès non autorisé |
| API tokens | Laravel Sanctum | Accès API externe |
| Contrôle d'accès | RBAC (3 rôles) + ACL granulaire par document | Élévation de privilèges |
| Intégrité fichiers | Checksum SHA256 sur chaque version | Altération silencieuse |
| Authenticité | QR code unique intégré dans le DOCX | Falsification de document |
| Signatures | SHA256(base64 canvas) + IP + user-agent | Répudiation de signature |
| Audit trail | Toutes les actions loguées (old/new values, IP, UA) | Non-traçabilité |
| Historique connexions | IP, user-agent, succès/échec | Intrusion non détectée |
| Permissions temporaires | `expires_at` sur `DocumentPermission` | Accès persistant non voulu |
| Verrous d'édition | UUID token + expiration automatique | Conflits d'édition |
| Soft delete | Corbeille avant suppression définitive | Suppression accidentelle |
| Partage sécurisé | Token 48 chars + expiration + révocation | Fuite de document |

---

## 11. POINTS D'AMÉLIORATION IDENTIFIÉS

Cette section recense les écarts techniques et les dettes identifiées lors de l'analyse du code. Ils sont classés par niveau de criticité pour aider à prioriser les corrections.

### ⚠️ Critiques — À corriger en priorité

- **Queue sync en production** : Le driver `QUEUE_CONNECTION=sync` dans le `docker-compose.yml` fait que `IndexDocumentText` s'exécute de manière synchrone dans la requête HTTP, bloquant la réponse pendant jusqu'à 120 secondes. Il faut passer à `database` ou `redis` avec un worker `php artisan queue:work`.
- **MySQL en prod vs PostgreSQL dans le code** : Les fonctions `plainto_tsquery()` (full-text search) et `ILIKE` (recherche insensible à la casse) sont spécifiques à PostgreSQL et ne fonctionnent pas sur MySQL. La recherche full-text est donc non fonctionnelle en production.
- **`shell_exec` pour pdftotext/tesseract** : Ces binaires ne sont pas installés dans l'image Docker Alpine. L'extraction de texte depuis les PDF et images échoue silencieusement en production.

### 🔶 Importants — À planifier

- **Pas de Redis** : Le cache file et les sessions file limitent la scalabilité horizontale et empêchent le pub/sub pour les notifications temps réel.
- **Config SMTP dynamique** : Appeler `config([...])` à chaque notification reconfigure le mailer Laravel à la volée, ce qui peut causer des effets de bord et impacte les performances.
- **Pas de rate limiting** sur `/login` et `/forgot-password` : Le système est vulnérable aux attaques par force brute sur les mots de passe.
- **Pas de 2FA** : Aucune authentification à deux facteurs n'est implémentée pour les comptes administrateurs.
- **Limit 100 hardcodée** dans `apiSearch()` : Sans pagination, les résultats sont tronqués arbitrairement à 100 documents.

### 🔵 Améliorations futures — Backlog

- Aucun test automatisé : PHPUnit est configuré mais aucun test unitaire ou fonctionnel n'a été écrit. La couverture de code est à 0%.
- Pas de WebSocket : Les notifications in-app nécessitent un rechargement de page pour apparaître. Une intégration Laravel Echo + Pusher/Soketi permettrait des notifications temps réel.
- Pas de preview PDF natif : Seuls les DOCX sont prévisualisables via Mammoth.js. Les PDF nécessiteraient une intégration PDF.js.
- Pas de chiffrement au repos : Les fichiers sur MinIO ne sont pas chiffrés côté serveur. Il faudrait activer le SSE (Server-Side Encryption) sur le bucket MinIO.

---

## 12. CONCLUSION

La GED Groupe Bama est un système documentaire complet et bien architecturé, qui couvre l'ensemble du cycle de vie d'un document d'entreprise. Son architecture Laravel MVC avec séparation services/jobs/middleware est saine et maintenable. Les fonctionnalités avancées — workflow d'approbation, versioning, signatures numériques, QR codes d'authenticité, WebDAV, indexation OCR — en font une solution à la hauteur des besoins d'une organisation de taille intermédiaire.

Les points d'amélioration identifiés sont réels mais non bloquants pour un usage quotidien à faible charge. La priorité absolue reste la **correction de la recherche full-text** (incompatibilité MySQL/PostgreSQL) et le **passage à une queue asynchrone** pour ne plus bloquer les requêtes HTTP lors de l'indexation des documents.

À terme, l'ajout de Redis, de tests automatisés et de notifications temps réel via WebSocket permettrait de faire passer ce système d'un outil interne robuste à une plateforme documentaire de niveau production enterprise.

---

*Document généré le 16 avril 2026 — Imperis Sarl pour Groupe Bama*
