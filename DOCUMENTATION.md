# TOOFLE — Cahier des charges technique & dossier de conception

> Réseau social professionnel pour **MANLAB** — les professionnels du secteur TECH.
> **Implémentation : PHP 8.2 pur + MySQL/MariaDB, sans framework.** Compatible XAMPP.
> Ce document remplace le plan « Laravel » initialement prevue : l'architecture,
> le modèle de données et les bonnes pratiques sont ici transposés à un socle
> **vanilla PHP** simple, lisible et déployable sur n'importe quel hébergement mutualisé.

---

## 1. Cahier des charges technique

### 1.1 Objectif

Plateforme web responsive (mobile-first) permettant à des professionnels TECH de :
se créer un profil, publier du contenu, interagir, discuter en temps réel,
échanger via une marketplace, rejoindre des groupes et participer à des événements.

### 1.2 Exigences fonctionnelles (couverture)

| # | Domaine | Fonctions livrées |
|---|---|---|
| 1 | Comptes & profils | Inscription/connexion, avatar, couverture, bio, poste, entreprise, localisation, site, centres d'intérêt, confidentialité par champ, changement de mot de passe/email |
| 2 | Fil d'actualité | Texte, image, vidéo (YouTube/Vimeo), lien, sondage ; visibilité public/amis/privé/personnalisé ; tri chronologique & par pertinence (score pondéré) ; réactions 6 types ; commentaires + sous-commentaires ; partages |
| 3 | Relations | Demandes d'amis (envoi, acceptation, refus), suppression d'ami, abonnements (follow), blocage/déblocage, signalement |
| 4 | Messagerie | Conversations 1-1 et de groupe, participants, pièces jointes, indicateur de saisie, accusés de lecture, historique, polling temps réel, notifications |
| 5 | Marketplace | Annonces (titre, description, prix, catégorie, état, localisation, jusqu'à 5 photos), recherche/filtres (mot-clé, catégorie, prix, localisation, tri), mise en avant, statut vendu, avis/notation vendeur, contact vendeur, modération |
| 6 | Groupes | Public / privé / secret, adhésion directe ou sur approbation, rôles admin/modérateur/membre, publications de groupe, bannissement, événements de groupe |
| 7 | Événements | Création (titre, dates, lieu, description, photo), visibilité, participation (participe/intéressé/décline), invitation d'amis, liste des participants |
| 8 | Recherche & découverte | Recherche globale (utilisateurs, groupes, événements, annonces, publications), suggestions d'amis et de groupes |
| 9 | Notifications | Centre centralisé, compteur temps réel, marquage lu/non lu, types (amis, réactions, commentaires, messages, événements, marketplace, système) |
| 10 | Administration | Statistiques, gestion utilisateurs (suspension, bannissement, réactivation, rôles), traitement des signalements, contenus signalés, journal de modération |
| 11 | Sécurité | CSRF, XSS, injection SQL, sessions, uploads, permissions |
| 12 | Performance | Index MySQL, requêtes préparées, pagination, polling configurable, cache applicatif simple |

### 1.3 Exigences non fonctionnelles

- **Responsive mobile-first** : points de rupture 640 px / 900 px / 1200 px + barre de navigation mobile.
- **Accessibilité** : HTML sémantique, rôles ARIA, `skip-link`, contrastes, focus visibles,
  `prefers-reduced-motion`, formulaires étiquetés.
- **Compatibilité** : PHP ≥ 8.2, MySQL/MariaDB ≥ 10.4, Apache (XAMPP) ou serveur PHP intégré.
- **Maintenabilité** : conventions PSR-1/PSR-12, séparation claire des responsabilités.
- **Scalabilité** : stateless côté web, index SQL, polling découplé, cache prêt (voir §7).

---

## 2. Architecture technique

### 2.1 Principe

Architecture **monolithique modulaire** en couches, sans dépendance externe :

```
   Client (HTML/CSS/JS vanilla)
             │  HTTP  +  AJAX JSON (polling)
             ▼
┌──────────────────────────────────────────────┐
│ index.php  —  Contrôleur frontal + routeur   │  liste blanche de pages
├──────────────────────────────────────────────┤
│ pages/*    —  Contrôleurs de page            │  validation, orchestration
├──────────────────────────────────────────────┤
│ includes/* —  Services & helpers             │  db, functions, post, chat, composer
├──────────────────────────────────────────────┤
│ MySQL (PDO, requêtes préparées)              │  24 tables InnoDB
└──────────────────────────────────────────────┘
   api/chat.php — point d'entrée JSON (chat, notifications)
```

### 2.2 Correspondance avec les couches demandées

| Couche « framework » | Équivalent ici | Fichiers |
|---|---|---|
| **Controllers** | Pages + API | `index.php`, `pages/*.php`, `api/chat.php` |
| **Services** | Helpers métier | `includes/post.php`, `includes/chat.php`, `includes/functions.php` |
| **Repositories** | Accès données centralisé | `includes/db.php` (`q`, `q_one`, `q_all`, `insert`, `update`) |
| **Form Requests / Validation** | Validation en tête de page | `csrf_verify()`, contrôles `if` explicites |
| **Events / Listeners** | Notifications découplées | `notify()` appelé après chaque action métier |
| **Jobs / Queues** | Journalisation + envoi asynchrone | `log_moderation()`, envoi d'emails différé (roadmap) |
| **Middleware** | `bootstrap.php` + `require_login()` | session, empreinte, timeout, rôles |

### 2.3 Flux d'une requête

1. `index.php` inclut `includes/bootstrap.php` (session, timeout, remember-me).
2. La page demandée est validée contre une **liste blanche**.
3. Si la page est privée et l'utilisateur non connecté → redirection vers `login`.
4. La page traite les **actions POST** (`action=...`) après `csrf_verify()`.
5. Rendu via `header.php` → contenu → `footer.php`.
6. Les interactions rapides (réactions, chat, notifications) passent par **AJAX/JSON**.

---

## 3. Modèle de données (MCD / MLD)

### 3.1 MCD simplifié

```
UTILISATEUR ──1,N── AMITIE ──N,1── UTILISATEUR
     │
     ├──1,N── PUBLICATION ──1,N── REACTION
     │            │  │
     │            │  ├──1,N── COMMENTAIRE (auto-relation parent/enfant)
     │            │  ├──1,N── OPTION_SONDAGE ──1,N── VOTE
     │            │  └──N,1── GROUPE (facultatif)
     │
     ├──1,N── APPARTENANCE ──N,1── GROUPE
     ├──1,N── PARTICIPATION ──N,1── EVENEMENT
     ├──1,N── ANNONCE ──1,N── PHOTO_ANNONCE
     │            └──1,N── AVIS_VENDEUR
     ├──1,N── MEMBRE_CONVERSATION ──N,1── CONVERSATION ──1,N── MESSAGE
     ├──1,N── NOTIFICATION
     ├──1,N── SIGNALEMENT
     ├──1,N── BLOCAGE ──N,1── UTILISATEUR
     └──1,N── ABONNEMENT ──N,1── UTILISATEUR
```

### 3.2 Tables (24)

| Table | Rôle | Relations clés |
|---|---|---|
| `users` | Comptes, profil, confidentialité, rôle, statut | — |
| `friendships` | Demandes/amitiés (`pending/accepted/declined`) | `requester_id`, `addressee_id` → users |
| `follows` | Abonnements asymétriques | `follower_id`, `followed_id` → users |
| `blocks` | Blocages | `user_id`, `blocked_id` → users |
| `posts` | Publications (type, visibilité, média, partage) | `user_id` → users, `group_id` → groups |
| `post_media` | Médias additionnels d'une publication | `post_id` → posts |
| `reactions` | Réactions (1 par utilisateur/publication) | `post_id`, `user_id` |
| `comments` | Commentaires + auto-relation `parent_id` | `post_id`, `user_id` |
| `poll_options` / `poll_votes` | Sondages et votes (1 vote/utilisateur) | `post_id` |
| `conversations` | Conversations 1-1 ou groupe | `created_by` |
| `conversation_members` | Participants + `last_read_at` | `conversation_id`, `user_id` |
| `messages` | Messages + pièces jointes | `conversation_id`, `user_id` |
| `typing_status` | Indicateur « en train d'écrire » | PK (`conversation_id`,`user_id`) |
| `groups` | Groupes (`public/private/secret`) | `owner_id` → users |
| `group_members` | Adhésion + rôle + statut | `group_id`, `user_id` |
| `events` | Événements | `creator_id` → users, `group_id` |
| `event_attendees` | RSVP (`going/interested/declined`) | `event_id`, `user_id` |
| `listings` | Annonces marketplace | `user_id` → users |
| `listing_images` | Photos d'annonce | `listing_id` |
| `listing_reviews` | Avis/notation vendeur | `seller_id`, `reviewer_id` |
| `notifications` | Notifications utilisateur | `user_id`, `actor_id` |
| `reports` | Signalements polymorphes (`target_type`+`target_id`) | `reporter_id` |
| `moderation_logs` | Journal des actions de modération | `moderator_id` |

### 3.3 Choix de conception

- **Clés étrangères InnoDB** partout (`ON DELETE CASCADE`) : intégrité garantie par la base.
- **Index** sur toutes les colonnes de jointure et de tri (`user_id`, `post_id`,
  `conversation_id`, `created_at`, `status`) pour tenir la charge.
- **Unicité métier** : `(requester_id, addressee_id)`, `(post_id, user_id)` pour les
  réactions, `(post_id, user_id)` pour les votes, `(conversation_id, user_id)`.
- **Polymorphisme maîtrisé** : `reports.target_type` + `target_id` évite 6 tables.
- **Auto-relation** `comments.parent_id` pour les sous-commentaires (1 niveau d'affichage).
- **Partages** : une publication de type `share` pointe vers l'originale
  (`shared_post_id`) — équivalent d'un `belongsTo` récursif.

---

## 4. Temps réel : messagerie & notifications

### 4.1 Implémentation livrée — polling AJAX

Choix assumé de la **simplicité** (contrainte « pas de stack complexe », hébergement
mutualisé possible, aucun daemon à maintenir) :

- `api/chat.php?action=fetch&conversation_id=X&after_id=Y` → renvoie uniquement les
  **nouveaux** messages (`id > after_id`), plus l'indicateur de saisie.
- `api/chat.php?action=send` → envoie un message (JSON + jeton CSRF).
- `api/chat.php?action=typing` → `INSERT … ON DUPLICATE KEY UPDATE` sur `typing_status`.
- `api/chat.php?action=notifications` → compteur non lu pour le badge de la cloche.
- Côté client (`assets/js/app.js`) : `setInterval` toutes les `chat_poll_ms` (3 s par défaut,
  réglable dans `config.php`), marquage automatique « lu » à l'ouverture (`last_read_at`).
- **Dégradation gracieuse** : si JavaScript est absent, le formulaire POST classique
  `messages&action=send` fonctionne (avec pièce jointe).

Avantages : aucun serveur supplémentaire, compatible XAMPP, déploiement trivial.
Limites : latence ≤ intervalle de polling et charge proportionnelle au nombre de clients.

### 4.2 Évolution vers du vrai push (recommandé ≥ 1 000 utilisateurs actifs)

Deux options, sans changer le modèle de données :

**Option A — WebSocket natif PHP (Ratchet / Workerman)**

```bash
composer require cboden/ratchet
php bin/chat-server.php          # écoute sur ws://localhost:8080
```
Le serveur PHP maintient les connexions ; `api/chat.php?action=send` publie le message
dans le hub (Redis pub/sub) ; le hub le diffuse aux abonnés de la conversation.

**Option B — Service tiers (Pusher / Ably / Faye)**
`send_message()` déclenche un événement HTTP vers le service, qui pousse au navigateur.
Aucun daemon à administrer.

**Option C — Server-Sent Events (SSE)**
`api/stream.php` ouvre un flux `text/event-stream` par client : plus simple qu'un WebSocket,
toujours en PHP, mais mobilise un worker PHP par connexion.

Dans tous les cas, le reste du code (modèle, rendu, permissions) reste **inchangé** :
seul le transport évolue.

### 4.3 Appels audio/vidéo (optionnel)

WebRTC pair-à-pair pour le média + le serveur PHP uniquement pour la **signalisation**
(échange d'offres/réponses SDP via une table `signal_messages`) et les serveurs STUN/TURN
(STUN gratuit ; TURN type coturn ou service managé pour les réseaux strictes).

---

## 5. Sécurité

| Menace | Contre-mesure implémentée |
|---|---|
| Injection SQL | PDO + **requêtes préparées** systématiques, `ATTR_EMULATE_PREPARES = false` |
| XSS | Échappement systématique via `e()` à l'affichage ; aucune donnée utilisateur brute |
| CSRF | Jeton de session comparé avec `hash_equals()` sur **chaque** POST |
| Mots de passe | `password_hash()` / `password_verify()` (bcrypt, coût par défaut) |
| Vol de session | Cookie `HttpOnly` + `SameSite=Lax`, empreinte UA+IP, `session_regenerate_id()` |
| Fixation de session | Régénération d'ID à la connexion |
| Session inactive | Expiration après `session_timeout` (2 h par défaut) |
| Upload malveillant | Extension + MIME réels vérifiés, taille limitée, nom aléatoire, PHP désactivé dans le dossier |
| Élévation de privilèges | Contrôles `is_admin()` / `is_moderator()` sur chaque action sensible |
| Inclusion de fichier | Liste blanche de pages dans le routeur |
| Énumération / spam | Notifications et actions limitées au périmètre autorisé ; signalement intégré |
| Données sensibles | Confidentialité par champ (email/téléphone/date), visibilité profil & posts |

**Recommandations de durcissement production** : HTTPS obligatoire, `display_errors=0`,
journalisation applicative, sauvegardes quotidiennes, rotation des logs,
rate limiting sur `login`/`register` (voir roadmap Sprint 6),
en-têtes `Content-Security-Policy`, `X-Frame-Options`, `X-Content-Type-Options`.

---

## 6. Estimation des ressources et du temps

### 6.1 Équipe recommandée

| Profil | Charge | Rôle |
|---|---|---|
| Développeur PHP/MySQL | 1 ETP | Back-end, modèle de données, API |
| Développeur front-end | 0,5 ETP | CSS/JS, responsive, accessibilité |
| DevOps | 0,2 ETP | CI/CD, serveurs, sauvegardes |
| QA / testeur | 0,3 ETP | Recette fonctionnelle et sécurité |
| Chef de projet / UX | 0,3 ETP | Pilotage agile, design |

### 6.2 Charge par module (jours-homme, base livrée incluse)

| Lot | Jours-homme |
|---|---|
| Socle (config, PDO, routeur, sécurité, layout) | 4 |
| Auth, profils, confidentialité, paramètres | 5 |
| Fil d'actualité (posts, médias, réactions, commentaires, sondages) | 8 |
| Relations (amis, abonnés, blocage, signalement) | 3 |
| Messagerie temps réel | 7 |
| Marketplace | 7 |
| Groupes | 5 |
| Événements | 4 |
| Recherche & découverte | 3 |
| Notifications | 3 |
| Administration & modération | 5 |
| Responsive, accessibilité, tests, documentation | 6 |
| **Total MVP livré** | **≈ 60 j/h** |
| Évolutions (WebRTC, paiement, email, i18n, PWA, cache Redis) | + 35 à 45 j/h |

---

## 7. Coûts d'infrastructure (estimation indicative)

> **Devise de la plateforme : le franc CFA (`XOF`).** Les montants ci-dessous sont
donnés en FCFA, avec la parité fixe officielle **1 € = 655,957 FCFA**. Dans le code,
la devise est configurable via `app.currency` dans `config.php` et formatée par
`money()` (le FCFA s'affiche sans décimales).

### 7.1 Scénario 1 — Lancement (< 5 000 utilisateurs, 200 simultanés)

| Poste | Choix | Coût mensuel |
|---|---|---|
| Hébergement web | VPS 2 vCPU / 4 Go (OVH, Scaleway, Hetzner) | 3 900 – 9 800 FCFA |
| Base de données | MySQL sur le même VPS | 0 FCFA |
| Nom de domaine | `.com` / `.fr` | ~650 FCFA /mois |
| Certificat TLS | Let's Encrypt | 0 FCFA |
| Sauvegardes | Snapshot + dump quotidien | 650 – 2 000 FCFA |
| Emails transactionnels | Brevo / Mailjet (offre gratuite) | 0 – 6 500 FCFA |
| **Total** | | **≈ 6 500 – 19 700 FCFA/mois** |

### 7.2 Scénario 2 — Croissance (5 000 – 50 000 utilisateurs)

| Poste | Choix | Coût mensuel |
|---|---|---|
| Web | 2 × VPS 4 vCPU / 8 Go + répartiteur de charge | 26 000 – 52 500 FCFA |
| Base de données | Instance MySQL managée (4 vCPU / 8 Go, réplique) | 39 000 – 98 000 FCFA |
| Cache / sessions | Redis managé 1 Go | 9 800 – 19 700 FCFA |
| Stockage objet (médias) | S3-compatible (Scaleway/OVH) 100 Go | 3 300 – 9 800 FCFA |
| Temps réel | Service push managé ou nœud WebSocket dédié | 0 – 26 000 FCFA |
| Emails transactionnels | 50 000 envois/mois | 9 800 – 19 700 FCFA |
| Monitoring | Uptime + logs | 0 – 16 400 FCFA |
| CDN | Cloudflare (offre gratuite → Pro) | 0 – 13 100 FCFA |
| **Total** | | **≈ 88 500 – 255 800 FCFA/mois** |

### 7.3 Scénario 3 — Échelle (> 50 000 utilisateurs)

Auto-scaling cloud (Kubernetes ou conteneurs managés), MySQL avec réplicas de lecture,
Redis cluster, stockage objet + CDN, files d'attente dédiées (envoi d'emails, notifications
push, transcodage vidéo) : **525 000 – 1 640 000 FCFA/mois**, hors coûts humains et marketing.

### 7.4 Services tiers optionnels

| Service | Usage | Coût |
|---|---|---|
| Stripe | Paiement marketplace | 1,4 % + 165 FCFA / transaction (UE) |
| PayPal | Alternative de paiement | ~2,9 % + 230 FCFA |
| Twilio / Brevo SMS | Codes et alertes | 33 – 66 FCFA / SMS |
| Sentry | Suivi des erreurs | 0 – 17 000 FCFA/mois |
| Google Maps | Cartes d'événements | gratuit jusqu'à un quota |

---

## 8. Roadmap agile (6 à 12 mois)

Sprints de **2 semaines**. Objectif : un MVP livrable au Sprint 6 (3 mois).

| Sprint | Durée | Livrables | Objectif de fin de sprint |
|---|---|---|---|
| **S0** | 1 sem. | Cadrage, design system, schéma SQL, environnements | Socle prêt |
| **S1** | 2 sem. | Auth, inscription, sessions sécurisées, layout responsive | On peut se connecter |
| **S2** | 2 sem. | Profils, avatars/couvertures, confidentialité, paramètres | Profil complet |
| **S3** | 2 sem. | Fil d'actualité : posts, images, visibilité, réactions | Publier et réagir |
| **S4** | 2 sem. | Commentaires, sous-commentaires, partages, sondages | Interactions complètes |
| **S5** | 2 sem. | Amis, abonnés, blocage, signalement, notifications | Réseau social v1 |
| **S6** | 2 sem. | Messagerie 1-1 + groupes, polling temps réel | **MVP prêt à tester** |
| **S7** | 2 sem. | Marketplace : annonces, photos, filtres | Place de marché v1 |
| **S8** | 2 sem. | Avis vendeurs, contact, gestion annonces, admin marketplace | Marketplace complète |
| **S9** | 2 sem. | Groupes : rôles, modération, publications, événements de groupe | Communautés |
| **S10** | 2 sem. | Événements : création, RSVP, invitations, rappels | Agenda |
| **S11** | 2 sem. | Recherche globale, suggestions, découverte | Découverte |
| **S12** | 2 sem. | Administration, modération, journal, statistiques | Gouvernance |
| **S13** | 2 sem. | Tests de charge, optimisation SQL, cache, accessibilité | Performance |
| **S14** | 2 sem. | Bêta privée, corrections, durcissement sécurité | Bêta stabilisée |
| **S15** | 2 sem. | Notifications email + push, PWA, i18n | Engagement |
| **S16** | 2 sem. | WebRTC (audio/vidéo), paiement Stripe (optionnel) | Fonctions avancées |
| **S17** | 2 sem. | Montée en charge, WebSocket, CDN, monitoring | Scalabilité |
| **S18** | 2 sem. | Lancement public, documentation, formation, support | **Lancement** |

### Définition de « Terminé »
Fonction testée manuellement + scénario de recette validé + pas de régression sur les tests
existants + documentation mise à jour + revue de sécurité pour les fonctions sensibles.

### Rituels agiles
Mêlée quotidienne (15 min), revue de sprint + démo, rétrospective, backlog priorisé
(MoSCoW), tableau Kanban, définition de « Prêt » en début de sprint.

---

## 9. Stratégie CI/CD (GitHub Actions)

### 9.1 Principes

- **CI** à chaque push et pull request : lint PHP, qualité de code, tests, audit de sécurité.
- **CD** : déploiement automatique en préproduction puis en production après validation.
- **Migrations SQL** appliquées automatiquement et sauvegarde de la base avant déploiement.

### 9.2 Exemple de workflow (`.github/workflows/ci.yml`)

```yaml
name: CI TOOFLE

on:
  push:
    branches: [main, develop]
  pull_request:

jobs:
  lint-and-test:
    runs-on: ubuntu-latest
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: toofle
        ports: ['3306:3306']
        options: >-
          --health-cmd="mysqladmin ping" --health-interval=10s
          --health-timeout=5s --health-retries=10
    steps:
      - uses: actions/checkout@v4

      - name: Installer PHP 8.2
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: pdo_mysql, mbstring, fileinfo, gd

      - name: Lint de tous les fichiers PHP
        run: |
          find . -name '*.php' -not -path './vendor/*' -print0 \
            | xargs -0 -n1 php -l

      - name: Vérifier le style (PSR-12)
        run: |
          curl -sSL https://cs.symfony.com/download/php-cs-fixer-v3.phar -o php-cs-fixer
          php php-cs-fixer fix --dry-run --diff --rules=@PSR12 src/ pages/ includes/ api/ 2>/dev/null || true

      - name: Importer le schéma et lancer les tests
        run: |
          mysql -h 127.0.0.1 -uroot -proot < sql/toofle.sql
          php tests/run.php

      - name: Audit de sécurité des dépendances
        run: composer audit || true
```

### 9.3 Exemple de déploiement (`.github/workflows/deploy.yml`)

```yaml
name: Déploiement

on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Sauvegarde de la base de production
        run: |
          mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" \
            > backup-$(date +%F-%H%M).sql
        env:
          DB_HOST: ${{ secrets.DB_HOST }}
          DB_USER: ${{ secrets.DB_USER }}
          DB_PASS: ${{ secrets.DB_PASS }}
          DB_NAME: ${{ secrets.DB_NAME }}

      - name: Déployer en préproduction
        run: |
          rsync -az --delete \
            --exclude '.git' --exclude 'assets/uploads' \
            ./ "$DEPLOY_USER@$DEPLOY_HOST:$DEPLOY_PATH"
        env:
          DEPLOY_USER: ${{ secrets.DEPLOY_USER }}
          DEPLOY_HOST: ${{ secrets.DEPLOY_HOST }}
          DEPLOY_PATH: ${{ secrets.PREPROD_PATH }}

      - name: Tests de fumée
        run: curl -fsS "https://preprod.toofle.example/index.php?p=login" > /dev/null

      - name: Promouvoir en production (après approbation)
        environment: production
        run: |
          rsync -az --delete ./ "$DEPLOY_USER@$DEPLOY_HOST:$DEPLOY_PATH"
```

### 9.4 Environnements

| Environnement | Branche | URL | Base |
|---|---|---|---|
| Développement | locale | `http://localhost/toofle` | `toofle` locale |
| Préproduction | `develop` | preprod.toofle.example | copie anonymisée |
| Production | `main` | toofle.example | base chiffrée + réplique |

**Qualité de code** : PHP-CS-Fixer (PSR-12), PHPStan niveau 5, PHPUnit pour les tests
unitaires des helpers (`e()`, `money()`, `time_ago()`, `friendship_status()`) et des
services (`send_message()`, `visibility_clause()`).

---

## 10. Analyse des risques et plan de mitigation

### 10.1 Risques techniques

| Risque | Prob. | Impact | Mitigation |
|---|---|---|---|
| Charge base de données à l'échelle | Moyenne | Élevé | Index, pagination, requêtes préparées, cache Redis, réplicas de lecture |
| Polling coûteux en CPU | Moyenne | Moyen | Intervalle configurable, `after_id` incrémental, passage à WebSocket/SSE |
| Contenu média volumineux | Élevée | Moyen | Limite 8 Mo, compression, stockage objet + CDN |
| Absence de file d'attente (emails) | Moyenne | Moyen | Introduire Redis + worker pour les envois différés |
| Dépendance à un hébergement unique | Faible | Élevé | Stateless, migrations versionnées, sauvegardes testées, IaC |
| Dette technique du PHP procédural | Moyenne | Moyen | Conventions PSR, revues de code, tests des services critiques |

### 10.2 Risques de sécurité

| Risque | Mitigation |
|---|---|
| Injection SQL | Requêtes préparées partout (aucune concaténation de données utilisateur) |
| XSS stocké (posts, commentaires, messages) | Échappement systématique à l'affichage via `e()` |
| CSRF | Jeton vérifié sur chaque POST + en-tête `X-CSRF-Token` pour l'AJAX |
| Brute force sur la connexion | **À ajouter** : limitation par IP + délai progressif (Sprint 14) |
| Spam et faux comptes | Signalement, modération, captcha sur l'inscription (roadmap) |
| Fuite de données personnelles (RGPD) | Confidentialité par champ, minimisation, droit à l'effacement, hébergement UE |
| Uploads malveillants | Validation extension + MIME + désactivation de PHP dans le dossier |
| Dépendances non maîtrisées | Aucune dépendance externe dans le MVP (surface d'attaque minimale) |

### 10.3 Risques produit et organisationnels

| Risque | Mitigation |
|---|---|
| Périmètre trop vaste | Livraison par sprints, MVP en 6 sprints, priorisation MoSCoW |
| Adoption faible | Programme bêta MANLAB, invitations, groupes métiers, contenu de démarrage |
| Monétisation incertaine | Modularité (marketplace commissionnable, abonnements pro, publicité ciblée) |
| Modération insuffisante | Panel admin dès le MVP + journal de modération + rôles modérateur |
| RGPD / hébergement France-UE | Hébergeur UE, registre de traitements, consentement, portabilité, DPO |

### 10.4 Recommandations de lancement et de croissance

**Avant le lancement**
1. Passer en HTTPS, désactiver l'affichage des erreurs, activer les journaux.
2. Créer un compte MySQL dédié (jamais `root`) avec droits restreints.
3. Mettre en place les sauvegardes automatiques et **tester une restauration**.
4. Ajouter la limitation de tentatives de connexion et un captcha à l'inscription.
5. Recruter 3 à 5 modérateurs, définir la charte de contenu.
6. Tester la charge (500 utilisateurs simultanés) et corriger les requêtes lentes.

**Pendant la croissance**
1. Migrer les médias vers un stockage objet + CDN.
2. Introduire Redis pour le cache, les sessions et les files d'attente.
3. Remplacer le polling par WebSocket/SSE pour le chat et les notifications.
4. Découpler en services si un domaine sature (messagerie, marketplace, recherche).
5. Ajouter un moteur de recherche dédié (Meilisearch, Typesense) au-delà de ~100 k documents.
6. Piloter par la donnée : rétention, temps de réponse P95, taux d'erreur, coût par utilisateur.

---

## 11. Questions ouvertes (recommandations)

| Question | Recommandation |
|---|---|
| **Taille de l'audience au lancement ?** | Concevoir pour 1 000 – 5 000 utilisateurs (VPS unique suffisant), architecture prête à scaler horizontalement. |
| **Monétisation ?** | Combiner : commission marketplace 2–5 %, offres « Pro » (mise en avant d'annonces, statistiques, badge), publicité ciblée TECH, sponsoring d'événements. |
| **Contraintes légales ?** | Hébergement dans l'UE, conformité RGPD (registre, consentement, effacement, portabilité), CGU + politique de confidentialité, mention de modération, DPO, conservation limitée des données. |

---

## 12. Évolutions recommandées après le MVP

1. **Applications mobiles** : PWA installable (rapide, faible coût) puis React Native/Flutter.
2. **Notifications push** : Web Push via Service Worker + FCM pour le mobile.
3. **Emails transactionnels** : Brevo/Mailjet pour notifications et réinitialisation de mot de passe.
4. **Appels audio/vidéo** : WebRTC avec serveur STUN/TURN.
5. **Paiement sécurisé** : Stripe Connect (paiement entre particuliers, séquestre).
6. **API publique** : endpoints REST documentés (OpenAPI) pour partenaires.
7. **Internationalisation** : fichiers de traduction FR/EN, formats de date et devise.
8. **Analytique** : tableau de bord produit (Matomo auto-hébergé, respectueux du RGPD).

---

© 2026 TOOFLE — MANLAB · Document technique du réseau social des professionnels TECH.
