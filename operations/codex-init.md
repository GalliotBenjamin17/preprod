# Codex Init Reference

Guide opératoire pour initialiser rapidement l'environnement Laravel local et me rappeler des conventions spécifiques au projet. À utiliser comme source de vérité lors de chaque prise en main.

## Prérequis Système

- PHP 8.2 avec les extensions usuelles Laravel (pdo_mysql, mbstring, etc.).
- Composer 2.3+.
- Node.js 16+ (+ npm).
- Serveur web Apache ou Nginx (Apache + vhosts sur le port 8005 recommandé).
- MySQL accessible pour les migrations et seeds.

## Récupération & Fichiers d'environnement

```bash
git clone <URL_DU_DEPOT> agregateur
cd agregateur
```

1. Copier le `.env` de l'ancien projet ou du template et vérifier les clés suivantes :
   - `APP_NAME=Laravel`
   - `APP_ENV=local`
   - `APP_KEY=base64:t0gLwwgAXMnGbQVJXvPF60vFoTguz3ybGpZhYkkSwks=`
   - `APP_DEBUG=true`
   - `APP_URL=http://localhost:8005`
   - `DISPLAYED_APP_URL=localhost`
   - `TELESCOPE_ENABLED=false`
   - `QUEUE_CONNECTION=database` (si utilisation des jobs)
   - `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
2. Si `.env` est absent, dupliquer `.env.example` puis appliquer les valeurs ci-dessus.

## Configuration Apache (ex. XAMPP)

`httpd.conf` :
- `Listen 8005`
- `ServerName localhost:8005`
- `Include conf/extra/httpd-vhosts.conf`

`httpd-vhosts.conf` :

```
<VirtualHost *:8005>
    ServerAlias larochelle.localhost
    DocumentRoot "C:/Users/BenjaminGalliot/Desktop/Applicationweb/pre-prod/preprod/public"
    AllowOverride All
</VirtualHost>
```

Redémarrer Apache après modification.

## Installation & Commandes de base

```bash
composer install
npm install
php artisan storage:link
php artisan telescope:publish
php artisan migrate
npm run build
```

## Artisan & Maintenance

- `php artisan init:app` : initialisation personnalisée post-migration.
- `php artisan config:clear`
- `php artisan route:clear`
- `php artisan cache:clear`
- `php artisan queue:work` : traiter les jobs en file (si `QUEUE_CONNECTION=database`).

## Rappels métier

- Les UUID utiles : terminal `994c4ef0-4459-475f-8c95-09a47bae81d0`, tenant `98f7f934-9cc7-4e6b-8bef-157b72b3cf88`.
- `Request::macro('subdomain')` (dans `app/Providers/AppServiceProvider.php`) utilise `getHost()` pour ignorer les ports lors du routage par sous-domaine.
- Front-end : Tailwind CSS + plugins (forms, typography, line-clamp), Mobiscroll, Chart.js, bundlé via Vite. Tous les assets sont dans `resources/` et compilés par `npm run dev|build`.

## Script d'initialisation

Le script `operations/init.ps1` orchestre les étapes clés :

- Vérifie/installe les dépendances Composer et npm (si `vendor/` ou `node_modules/` manquants).
- Duplique `.env.example` vers `.env` si nécessaire.
- Exécute les commandes Laravel de nettoyage (`config:clear`, `route:clear`, `cache:clear`), `storage:link`, `telescope:publish`, `migrate`.
- Optionnellement reconstruit les assets (`npm run build` ou `npm run dev` selon le paramètre).

Voir la section suivante pour son utilisation détaillée.

## Utilisation recommandée

1. Lancer `powershell.exe` à la racine du projet.
2. Exécuter `.\operations\init.ps1` (avec les options voulues) pour préparer l'environnement.
3. Utiliser ce document comme aide-mémoire pour toute autre commande artisan/infra.

Mettre à jour ce fichier à chaque évolution des procédures locales afin qu'il reste la référence unique.
