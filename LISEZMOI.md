# GESMART (PHP pur) - connexion

Base de données : **aucune modification de structure**. Le code lit les tables existantes
(`users`, `groups`, `rules`, `unities`) et écrit seulement dans `logs` et `users.last_login_at`.

## Essai en local (Windows, XAMPP ou Laragon)

1. **Importer la base** : créez une base `abdoumtgesmart` (interclassement `utf8mb4_general_ci`) puis importez
   le fichier SQL. Le dump complet pèse 163 Mo : utilisez plutôt la version « sans logs » fournie,
   ou la ligne de commande :
   `mysql -u root abdoumtgesmart < abdoumtgesmart_local_sans_logs.sql`
2. **Configuration** : `config.php` est déjà réglé pour XAMPP (hôte 127.0.0.1, utilisateur `root`, mot de passe vide).
   Adaptez `name`, `user` et `pass` si besoin.
3. **Vérifier la base** : `php tools/check_db.php`
4. **Lancer** : dans le dossier du projet, `php -S localhost:8000 -t public`
   puis ouvrir http://localhost:8000
5. **Se connecter** avec un compte existant de la table `users`.
6. Si la connexion échoue : `php tools/check_login.php VOTRELOGIN` indique si le compte est suspendu et si
   le mot de passe correspond au hachage stocké (SHA-1 supposé).

Pour le logo et l'image de fond : enregistrez `logo.svg` et `bg.webp` (depuis votre site, dossier
`/assets/img/`) dans `public/assets/img/`.

## Sous Apache (htdocs)

Copiez le dossier dans `htdocs/gesmart-php`, mettez `'base_path' => '/gesmart-php/public'` dans `config.php`
et ouvrez http://localhost/gesmart-php/public/ (mod_rewrite activé).

## Mise en ligne

Copiez `config.example.php` en `config.php` avec les vrais paramètres MySQL, `debug => false`, `csp => true`,
et pointez le site sur le dossier `public/`.

## Points d'attention

- `rehash_on_login` doit rester à `false` tant que l'ancienne application utilise la même base : sinon les
  mots de passe convertis (password_hash) ne fonctionneraient plus dans l'ancienne application.
- Ce code n'a pas pu être exécuté dans l'environnement où il a été écrit (PHP absent) : au premier lancement,
  envoyez-moi le message d'erreur éventuel (affiché en mode debug, et dans `storage/logs/php-error.log`).
- Ajouter un module (CRUD) : créer `app/Models/XxxModel.php` (une table = quelques lignes grâce à `BaseModel`),
  un contrôleur, des vues, puis les routes dans `app/routes.php` avec `perm = true` pour appliquer les droits
  de la table `rules`.
