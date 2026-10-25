# TOOFLE 🚀

**Réseau social complet pour les professionnels du secteur TECH — par MANLAB**

Application **PHP 8.2 pur** (sans framework) + **MySQL/MariaDB**, conçue pour fonctionner
directement avec **XAMPP**. Interface **responsive mobile-first**, messagerie temps réel,
marketplace (prix en **franc CFA / XOF**), groupes, événements, notifications,
modération et administration.

**Icônes** : [Bootstrap Icons](https://icons.getbootstrap.com) v1.11.3, installées en local
(`assets/vendor/bootstrap-icons/`) — aucune connexion internet requise. Toute icône de
l'interface passe par le helper `icon('nom-icone')`.

---

## 1. Contenu du projet

| Fonctionnalité | État | Fichiers principaux |
|---|---|---|
| Authentification & profils | ✅ | `pages/login.php`, `pages/register.php`, `pages/profile.php`, `pages/settings.php` |
| Fil d'actualité (posts, réactions, commentaires, partages, sondages) | ✅ | `pages/feed.php`, `pages/post.php`, `includes/post.php` |
| Amis / abonnés / blocage / signalement | ✅ | `pages/friends.php` |
| Messagerie temps réel (1-1 + groupes, pièces jointes, accusés de lecture) | ✅ | `pages/messages.php`, `api/chat.php`, `includes/chat.php` |
| Marketplace (annonces, filtres, avis, mise en avant) | ✅ | `pages/marketplace.php`, `pages/listing.php` |
| Groupes & communautés (rôles, modération, privé/secret) | ✅ | `pages/groups.php`, `pages/group.php` |
| Événements (création, invités, participation) | ✅ | `pages/events.php`, `pages/event.php` |
| Recherche globale & découverte | ✅ | `pages/search.php` |
| Notifications (temps réel + centre de notifications) | ✅ | `pages/notifications.php`, `api/chat.php` |
| Administration & modération | ✅ | `pages/admin.php` |
| Page d'erreur (403, 404, 419, 429, 500, 503) | ✅ | `includes/error.php`, `pages/error.php` |
| Sécurité (CSRF, XSS, SQLi, sessions, uploads) | ✅ | `includes/functions.php`, `includes/bootstrap.php` |
| Appels audio/vidéo (WebRTC) | ⏳ optionnel | voir `DOCUMENTATION.md` §7 |
| Paiement marketplace (Stripe/PayPal) | ⏳ optionnel | `config.php` → `payment` |

---

## 2. Prérequis

- **XAMPP** avec **PHP 8.2+** et **MySQL/MariaDB 10.4+**
- Un navigateur moderne (Chrome, Edge, Firefox, Safari)
- Aucune dépendance Composer n'est nécessaire : le projet est 100 % autonome.

---

## 3. Installation en 3 étapes

### Étape 1 — Placer le projet dans XAMPP

Copiez le dossier du projet dans `C:\xampp\htdocs\` (par ex. `C:\xampp\htdocs\toofle`).
Vous pouvez aussi le laisser où il est et utiliser le serveur PHP intégré (voir §5).

### Étape 2 — Créer la base de données

Démarrez **Apache** et **MySQL** depuis le panneau de contrôle XAMPP, puis :

**Option A — en ligne de commande (recommandé)**
```bash
C:\xampp\mysql\bin\mysql.exe -u root < sql/toofle.sql
```

**Option B — via phpMyAdmin**
1. Ouvrez http://localhost/phpmyadmin
2. Onglet **Importer** → choisissez le fichier `sql/toofle.sql` → **Exécuter**

Cela crée la base `toofle`, les 24 tables et un jeu de données de démonstration.

### Étape 3 — Accéder à l'application

```
http://localhost/toofle/index.php
```
(ou `http://localhost/toofle/` si le dossier s'appelle `toofle`)

---

## 4. Comptes de démonstration

| Rôle | Email | Mot de passe |
|---|---|---|
| Administrateur | `admin@toofle.test` | `Password123!` |
| Utilisateur | `awa@toofle.test` | `Password123!` |
| Utilisateur | `karim@toofle.test` | `Password123!` |
| Utilisateur | `nina@toofle.test` | `Password123!` |

> ⚠️ **En production** : supprimez ou changez immédiatement ces comptes, et
> mettez à jour la configuration dans `config.php` (utilisateur MySQL avec mot de passe).

---

## 5. Lancer sans Apache (serveur intégré PHP)

Depuis la racine du projet :
```bash
C:\xampp\php\php.exe -S localhost:8080 -t .
```
Puis ouvrez http://localhost:8080/index.php

---

## 6. Configuration

Tout se règle dans **`config.php`** :

```php
'db' => [
    'host' => '127.0.0.1',
    'port' => 3306,
    'name' => 'toofle',
    'user' => 'root',
    'pass' => '',          // mot de passe MySQL (vide par défaut sous XAMPP)
],
'app' => [
    'session_timeout' => 7200,        // durée de session en secondes
    'max_upload_size' => 8388608,     // 8 Mo
    'chat_poll_ms'    => 3000,        // fréquence de rafraîchissement du chat
],
```

---

## 7. Structure des fichiers

```
toofle/
├── index.php              # Contrôleur frontal : index.php?p=page
├── config.php             # Configuration (BDD, app, paiement)
├── sql/toofle.sql         # Schéma complet + données de démo
├── includes/
│   ├── bootstrap.php      # Session sécurisée, timeout, remember-me, erreurs
│   ├── error.php          # Gestion des erreurs + page d'erreur autonome
│   ├── db.php             # Connexion PDO + helpers (q, q_one, insert, update)
│   ├── functions.php      # Sécurité, auth, uploads, notifications, icônes, devise
│   ├── header.php         # En-tête + barre de navigation + ouverture du layout
│   ├── footer.php         # Pied de page + navigation mobile + scripts
│   ├── post.php           # Requêtes et rendu des publications
│   ├── composer.php       # Composeur de publication réutilisable
│   └── chat.php           # Helpers de messagerie
├── pages/                 # Une page = un fichier (feed, profile, messages, …)
├── api/chat.php           # API JSON du chat et des notifications (AJAX)
├── storage/logs/          # Journaux d'erreurs (accès web refusé)
└── assets/
    ├── css/style.css      # Feuille de style responsive mobile-first
    ├── js/app.js          # Interactions : réactions AJAX, chat, modales
    ├── vendor/            # Bootstrap Icons (CSS + polices, hors ligne)
    └── uploads/           # Fichiers téléversés (PHP désactivé)
```

### Routage

Le routage est volontairement simple : `index.php?p=<page>`, avec une **liste blanche**
de pages autorisées (aucune inclusion de fichier arbitraire possible).

| URL | Page |
|---|---|
| `index.php?p=feed` | Fil d'actualité |
| `index.php?p=profile&id=2` | Profil utilisateur |
| `index.php?p=messages&c=1` | Conversation |
| `index.php?p=marketplace` | Marketplace |
| `index.php?p=admin` | Administration (admin/modérateur) |
| `index.php?p=error&code=404` | Page d'erreur (404, 403, 500…) |

---

## 8. Notes de sécurité implémentées

- **Injection SQL** : 100 % des requêtes utilisent **PDO avec requêtes préparées**.
- **XSS** : toute sortie passe par `e()` (`htmlspecialchars` + `ENT_QUOTES`).
- **CSRF** : jeton par session vérifié sur chaque POST (`csrf_verify()`).
- **Mots de passe** : `password_hash()` (bcrypt) — jamais stockés en clair.
- **Sessions** : cookie `HttpOnly` + `SameSite=Lax`, empreinte navigateur/IP,
  régénération d'ID à la connexion, expiration par inactivité.
- **Uploads** : extension + type MIME vérifiés, taille limitée, nom aléatoire,
  exécution PHP désactivée dans `assets/uploads/`.
- **Accès** : contrôle de visibilité des publications et des profils à chaque requête ;
  contrôle des rôles sur les actions d'administration.

---

## 9. Devise & page d'erreur

**Devise — franc CFA (`XOF`)**

- Réglée dans `config.php` : `app.currency => 'XOF'`.
- Formatée par `money($montant, $devise)` ; le FCFA s'affiche **sans décimales**
  (`950 000 FCFA`). L'euro et le dollar restent supportés si besoin.
- Les saisies de prix acceptent les séparateurs courants (`950 000`, `12,5`, `12.5`)
  grâce à `parse_price()`.
- Les annonces de démonstration sont libellées en FCFA.

**Page d'erreur**

`includes/error.php` fournit `error_response($code, $titre, $message)` :

- rend une page d'erreur illustrée (icône Bootstrap, code, message, actions) ;
- répond automatiquement en **JSON** si la requête vient de `api/` ou est un appel AJAX ;
- codes gérés : 400, 403, 404, 405, 419, 429, 500, 503.

Elle est branchée sur : les pages inconnues (404), l'accès refusé à l'admin (403),
le jeton CSRF invalide (419), l'indisponibilité de MySQL (503) et **toutes les
exceptions / erreurs fatales PHP** (500) via `error_register_handlers()`.
Les détails techniques sont écrits dans `storage/logs/php-error.log`
(le détail est affiché sur la page uniquement si `app.debug` vaut `true`).

Accès direct pour tester : `index.php?p=error&code=500`.

---

## 10. Documentation complète

Voir **`DOCUMENTATION.md`** pour :
1. l'architecture technique détaillée et le découpage en couches ;
2. le MCD/MLD (modèle de données) et les relations ;
3. la mise en place du **temps réel** (polling AJAX, et évolution vers WebSocket) ;
4. l'estimation des coûts d'infrastructure ;
5. la **roadmap en sprints** (6 à 12 mois) ;
6. l'analyse des risques (techniques, sécurité, performance) ;
7. la stratégie de **CI/CD** (GitHub Actions).

---

© 2026 TOOFLE — MANLAB. Projet de démonstration à usage pédagogique et professionnel.
